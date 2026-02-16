/**
 * Global Preferences Loader
 * Loads and applies user preferences across the application
 */

// Default preferences
const DEFAULT_PREFERENCES = {
    theme: 'light',
    font_size: 'medium',
    compact_mode: false,
    sidebar_collapsed: false,
    dashboard_default_view: 'overview',
    dashboard_refresh_interval: '60',
    dashboard_show_quick_stats: true,
    pos_default_payment: 'cash',
    pos_auto_print: false,
    pos_sound_enabled: true,
    pos_show_images: true,
    notify_low_stock: true,
    notify_daily_summary: false,
    notify_email: false,
    items_per_page: '25',
    date_format: 'DD/MM/YYYY',
    time_format: '24h',
    number_format: '1,234.56',
    language: 'en',
    timezone: 'Africa/Nairobi'
};

class PreferencesManager {
    constructor() {
        this.preferences = { ...DEFAULT_PREFERENCES };
        this.loaded = false;
    }

    /**
     * Load preferences from LocalStorage and API
     */
    async load() {
        // 1. Load from LocalStorage for immediate application
        try {
            const stored = localStorage.getItem('user_preferences');
            if (stored) {
                this.preferences = { ...DEFAULT_PREFERENCES, ...JSON.parse(stored) };
                this.applyAll(); // Apply immediately
            }
        } catch (e) { console.error('Error loading from localStorage', e); }

        // 2. Load from API for source of truth
        try {
            const response = await axios.get('/settings/user/preferences');
            // Merge: Default < LocalStorage < API
            // API takes precedence as "cloud" source of truth, but usually matches
            this.preferences = { ...DEFAULT_PREFERENCES, ...this.preferences, ...response.data };
            
            // Sync API results back to LocalStorage
            localStorage.setItem('user_preferences', JSON.stringify(this.preferences));
            
            // Sync critical keys explicitly for app.blade.php script
            if (this.preferences.theme) localStorage.setItem('theme', this.preferences.theme);
            
            this.loaded = true;
            return this.preferences;
        } catch (error) {
            console.error('Failed to load preferences:', error);
            // Fallback to whatever we have (defaults or local)
            this.loaded = true;
            return this.preferences;
        }
    }

    /**
     * Save preferences
     */
    async save(newPreferences) {
        // Optimistic update
        this.preferences = { ...this.preferences, ...newPreferences };
        this.applyAll();
        
        // Save to LocalStorage
        localStorage.setItem('user_preferences', JSON.stringify(this.preferences));
        
        // Sync critical keys
        if (this.preferences.theme) localStorage.setItem('theme', this.preferences.theme);

        // Save to API
        try {
            await axios.put('/settings/user/preferences', { preferences: this.preferences });
            return true;
        } catch (error) {
            console.error('Failed to save preferences to server:', error);
            // We typically don't revert UI here to avoid jank, but warn user
            return false;
        }
    }

    /**
     * Get a specific preference value
     */
    get(key) {
        return this.preferences[key] ?? DEFAULT_PREFERENCES[key];
    }

    /**
     * Helper to format numbers based on preference
     */
    formatNumber(value) {
        const format = this.get('number_format'); // '1,234.56', '1.234,56', '1 234.56'
        const val = parseFloat(value);
        if (isNaN(val)) return value;

        let parts = val.toFixed(2).split('.');
        let integerPart = parts[0];
        let decimalPart = parts[1];

        if (format === '1.234,56') {
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
            return `${integerPart},${decimalPart}`;
        } else if (format === '1 234.56') {
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, " ");
            return `${integerPart}.${decimalPart}`;
        } else {
            // Default 1,234.56
            integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            return `${integerPart}.${decimalPart}`;
        }
    }

    /**
     * Helper to format dates based on preference
     */
    formatDate(dateStr) {
        // Simple implementation, in a real app would use moment/date-fns
        const format = this.get('date_format');
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;

        const d = String(date.getDate()).padStart(2, '0');
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const y = date.getFullYear();

        if (format === 'MM/DD/YYYY') return `${m}/${d}/${y}`;
        if (format === 'YYYY-MM-DD') return `${y}-${m}-${d}`;
        return `${d}/${m}/${y}`; // Default DD/MM/YYYY
    }

    /**
     * Check if sound is enabled
     */
    isSoundEnabled() {
        const val = this.get('pos_sound_enabled');
        return val === true || val === 'true' || val === 1 || val === '1';
    }

    /**
     * Apply theme preference
     */
    applyTheme() {
        const theme = this.get('theme');
        const html = document.documentElement;
        
        if (theme === 'dark') {
            html.classList.add('dark');
        } else if (theme === 'light') {
            html.classList.remove('dark');
        } else if (theme === 'auto') {
            // Auto theme based on system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                html.classList.add('dark');
            } else {
                html.classList.remove('dark');
            }
        }
    }

    /**
     * Apply font size preference
     */
    applyFontSize() {
        const fontSize = this.get('font_size');
        const body = document.body;
        
        // Remove existing font size classes
        body.classList.remove('font-size-small', 'font-size-medium', 'font-size-large');
        
        // Apply new font size class
        body.classList.add(`font-size-${fontSize}`);
    }

    /**
     * Apply compact mode preference
     */
    applyCompactMode() {
        const compactMode = this.get('compact_mode');
        const body = document.body;
        
        if (compactMode === true || compactMode === 'true' || compactMode === 1 || compactMode === '1') {
            body.classList.add('compact-mode');
        } else {
            body.classList.remove('compact-mode');
        }
    }

    /**
     * Apply sidebar collapsed preference
     */
    applySidebarCollapsed() {
        const collapsed = this.get('sidebar_collapsed');
        const sidebar = document.querySelector('aside');
        
        if (!sidebar) return; // Login page or similar
        
        if (collapsed === true || collapsed === 'true' || collapsed === 1 || collapsed === '1') {
            sidebar.classList.add('w-20');
            sidebar.classList.remove('w-64');
            // Hide text labels if needed, usually done via CSS based on parent class
            document.body.classList.add('sidebar-collapsed');
        } else {
            sidebar.classList.remove('w-20');
            sidebar.classList.add('w-64');
            document.body.classList.remove('sidebar-collapsed');
        }
    }

    /**
     * Apply all visual preferences
     */
    applyAll() {
        this.applyTheme();
        this.applyFontSize();
        this.applyCompactMode();
        this.applySidebarCollapsed();
    }

    /**
     * Initialize preferences - load and apply
     */
    async init() {
        await this.load();
        this.applyAll();
        
        // Listen for system theme changes if auto theme is enabled
        if (this.get('theme') === 'auto') {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
                this.applyTheme();
            });
        }
    }
}

// Create global instance
window.preferencesManager = new PreferencesManager();

// Export for module usage
export default window.preferencesManager;
