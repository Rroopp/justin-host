@extends('layouts.app')

@section('content')
<div x-data="categoryManager()" x-init="loadAll()">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
            <p class="mt-2 text-sm text-gray-600">Manage categories and subcategories used in Inventory</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.index') }}" class="bg-white border border-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-50">
                Back to Inventory
            </a>
            <button type="button" @click="openCategoryModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Add Category
            </button>
            <button type="button" @click="openSubcategoryModal()" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                Add Subcategory
            </button>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Categories -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="p-4 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Categories</h2>
                    <input type="text" x-model="categorySearch" placeholder="Search categories..." class="rounded-md border-gray-300">
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="cat in filteredCategories()" :key="cat.id">
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="cat.name"></td>
                                <td class="px-4 py-3 text-sm text-gray-500" x-text="cat.description || '-'"></td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-3" @click="editCategory(cat)">Edit</button>
                                    <button class="text-red-600 hover:text-red-900" @click="deleteCategory(cat)">Delete</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredCategories().length === 0">
                            <td colspan="3" class="px-4 py-6 text-sm text-gray-500 text-center">No categories found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Subcategories -->
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="p-4 border-b">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-lg font-semibold text-gray-900">Subcategories</h2>
                    <div class="flex gap-2">
                        <select x-model="subcategoryCategoryId" class="rounded-md border-gray-300">
                            <option value="">All Categories</option>
                            <template x-for="cat in categories" :key="cat.id">
                                <option :value="String(cat.id)" x-text="cat.name"></option>
                            </template>
                        </select>
                        <input type="text" x-model="subcategorySearch" placeholder="Search subcategories..." class="rounded-md border-gray-300">
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subcategory</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <template x-for="sub in filteredSubcategories()" :key="sub.id">
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900" x-text="sub.name"></td>
                                <td class="px-4 py-3 text-sm text-gray-700" x-text="categoryName(sub.category_id)"></td>
                                <td class="px-4 py-3 text-sm text-gray-500" x-text="sub.description || '-'"></td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <button class="text-indigo-600 hover:text-indigo-900 mr-3" @click="editSubcategory(sub)">Edit</button>
                                    <button class="text-red-600 hover:text-red-900" @click="deleteSubcategory(sub)">Delete</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="filteredSubcategories().length === 0">
                            <td colspan="4" class="px-4 py-6 text-sm text-gray-500 text-center">No subcategories found</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Category Modal -->
    <div x-show="showCategoryModal" x-transition.opacity class="fixed z-50 inset-0 overflow-y-auto" @keydown.escape.window="closeCategoryModal()">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeCategoryModal()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveCategory()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="categoryForm.id ? 'Edit Category' : 'Add Category'"></h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" x-model="categoryForm.name" required class="mt-1 block w-full rounded-md border-gray-300" x-ref="categoryNameInput">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea x-model="categoryForm.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" @click="closeCategoryModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Subcategory Modal -->
    <div x-show="showSubcategoryModal" x-transition.opacity class="fixed z-50 inset-0 overflow-y-auto" @keydown.escape.window="closeSubcategoryModal()">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black/50 backdrop-blur-sm transition-opacity" @click="closeSubcategoryModal()"></div>
            <div class="relative inline-block align-middle bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form @submit.prevent="saveSubcategory()">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" x-text="subcategoryForm.id ? 'Edit Subcategory' : 'Add Subcategory'"></h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Category *</label>
                                <select x-model="subcategoryForm.category_id" required class="mt-1 block w-full rounded-md border-gray-300">
                                    <option value="" disabled>Select category</option>
                                    <template x-for="cat in categories" :key="cat.id">
                                        <option :value="String(cat.id)" x-text="cat.name"></option>
                                    </template>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Name *</label>
                                <input type="text" x-model="subcategoryForm.name" required class="mt-1 block w-full rounded-md border-gray-300" x-ref="subcategoryNameInput">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea x-model="subcategoryForm.description" rows="3" class="mt-1 block w-full rounded-md border-gray-300"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                            Save
                        </button>
                        <button type="button" @click="closeSubcategoryModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
window.categoryManager = function () {
    return {
        categories: [],
        subcategories: [],

        categorySearch: '',
        subcategorySearch: '',
        subcategoryCategoryId: '',

        showCategoryModal: false,
        showSubcategoryModal: false,

        categoryForm: { id: null, name: '', description: '' },
        subcategoryForm: { id: null, name: '', category_id: '', description: '' },

        async loadAll() {
            try {
                const res = await axios.get('/categories');
                this.categories = res.data || [];
                this.subcategories = (this.categories || []).flatMap(c => (c.subcategories || []).map(s => ({ ...s })));
            } catch (e) {
                alert('Failed to load categories: ' + (e.response?.data?.message || e.message));
            }
        },

        categoryName(categoryId) {
            const id = String(categoryId ?? '');
            const cat = this.categories.find(c => String(c.id) === id);
            return cat ? cat.name : '-';
        },

        filteredCategories() {
            const q = (this.categorySearch || '').toLowerCase().trim();
            if (!q) return this.categories;
            return (this.categories || []).filter(c =>
                (c.name || '').toLowerCase().includes(q) ||
                (c.description || '').toLowerCase().includes(q)
            );
        },

        filteredSubcategories() {
            const q = (this.subcategorySearch || '').toLowerCase().trim();
            const catId = (this.subcategoryCategoryId || '').trim();

            return (this.subcategories || []).filter(s => {
                if (catId && String(s.category_id) !== String(catId)) return false;
                if (!q) return true;
                const nameMatch = (s.name || '').toLowerCase().includes(q);
                const descMatch = (s.description || '').toLowerCase().includes(q);
                return nameMatch || descMatch;
            });
        },

        openCategoryModal() {
            this.categoryForm = { id: null, name: '', description: '' };
            this.showCategoryModal = true;
            this.$nextTick(() => this.$refs.categoryNameInput?.focus());
        },

        editCategory(cat) {
            this.categoryForm = { id: cat.id, name: cat.name, description: cat.description || '' };
            this.showCategoryModal = true;
            this.$nextTick(() => this.$refs.categoryNameInput?.focus());
        },

        closeCategoryModal() {
            this.showCategoryModal = false;
            this.categoryForm = { id: null, name: '', description: '' };
        },

        async saveCategory() {
            try {
                if (this.categoryForm.id) {
                    await axios.put(`/categories/${this.categoryForm.id}`, this.categoryForm);
                } else {
                    await axios.post('/categories', this.categoryForm);
                }
                this.closeCategoryModal();
                await this.loadAll();
                alert('Category saved successfully');
            } catch (e) {
                alert('Error saving category: ' + (e.response?.data?.message || e.message));
            }
        },

        async deleteCategory(cat) {
            if (!confirm(`Delete category "${cat.name}"?`)) return;
            try {
                await axios.delete(`/categories/${cat.id}`);
                await this.loadAll();
                alert('Category deleted successfully');
            } catch (e) {
                alert('Error deleting category: ' + (e.response?.data?.message || e.message));
            }
        },

        openSubcategoryModal() {
            const defaultCat = this.categories?.[0]?.id;
            this.subcategoryForm = { id: null, name: '', category_id: defaultCat ? String(defaultCat) : '', description: '' };
            this.showSubcategoryModal = true;
            this.$nextTick(() => this.$refs.subcategoryNameInput?.focus());
        },

        editSubcategory(sub) {
            this.subcategoryForm = {
                id: sub.id,
                name: sub.name,
                category_id: String(sub.category_id),
                description: sub.description || ''
            };
            this.showSubcategoryModal = true;
            this.$nextTick(() => this.$refs.subcategoryNameInput?.focus());
        },

        closeSubcategoryModal() {
            this.showSubcategoryModal = false;
            this.subcategoryForm = { id: null, name: '', category_id: '', description: '' };
        },

        async saveSubcategory() {
            try {
                const payload = { ...this.subcategoryForm, category_id: Number(this.subcategoryForm.category_id) };
                if (this.subcategoryForm.id) {
                    await axios.put(`/subcategories/${this.subcategoryForm.id}`, payload);
                } else {
                    await axios.post('/subcategories', payload);
                }
                this.closeSubcategoryModal();
                await this.loadAll();
                alert('Subcategory saved successfully');
            } catch (e) {
                alert('Error saving subcategory: ' + (e.response?.data?.message || e.message));
            }
        },

        async deleteSubcategory(sub) {
            if (!confirm(`Delete subcategory "${sub.name}"?`)) return;
            try {
                await axios.delete(`/subcategories/${sub.id}`);
                await this.loadAll();
                alert('Subcategory deleted successfully');
            } catch (e) {
                alert('Error deleting subcategory: ' + (e.response?.data?.message || e.message));
            }
        },
    };
};
</script>
@endsection


