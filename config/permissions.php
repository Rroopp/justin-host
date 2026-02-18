<?php

/**
 * Permission Configuration
 *
 * Defines all available permissions in the system and default permissions
 * for each role. Staff can be granted additional permissions beyond their
 * role defaults, including admin-level permissions.
 */

return [
    /**
     * All available permissions in the system
     * Organized by category for UI display
     */
    'all' => [
        'inventory' => [
            'inventory.view' => 'View Inventory',
            'inventory.edit' => 'Edit/Add/Delete (Manage)',
            'inventory.adjust' => 'Adjust Stock',
            'inventory.categories' => 'View/Manage Categories',
            'inventory.batches' => 'View/Manage Batches',
        ],
        'pos_sales' => [
            'pos.access' => 'Access POS',
            'pos.discount' => 'Give Discounts',
            'sales.view' => 'View Sales History (All)',
            'sales.refund' => 'Process Refunds',
            'sales.invoices' => 'View/Manage Invoices',
        ],
        'reports' => [
            'reports.view' => 'View Reports',
            'finance.view' => 'View Financials',
            'reports.analytics' => 'View Analytics Dashboard',
        ],
        'accounting' => [
            'accounting.view' => 'View Accounting Dashboard',
            'accounting.manage' => 'Manage Accounts & Entries',
            'accounting.reports' => 'View Financial Reports',
            'accounting.journal' => 'Manage Journal Entries',
            'accounting.periods' => 'Manage Accounting Periods',
        ],
        'payroll' => [
            'payroll.view' => 'View Payroll',
            'payroll.process' => 'Process Payroll & Payslips',
            'payroll.deductions' => 'Manage Deduction Types',
            'payroll.reimbursements' => 'Approve Reimbursements',
        ],
        'suppliers_customers' => [
            'suppliers.view' => 'View Suppliers',
            'suppliers.manage' => 'Manage Suppliers',
            'customers.view' => 'View Customers',
            'customers.manage' => 'Manage Customers',
        ],
        'orders_rentals' => [
            'orders.create' => 'Create Orders (LPO)',
            'orders.approve' => 'Approve Orders',
            'rentals.view' => 'View Rentals/Assets',
            'rentals.manage' => 'Manage Rentals',
        ],
        'budgets' => [
            'budgets.view' => 'View Budgets',
            'budgets.manage' => 'Manage Budgets',
            'budgets.approve' => 'Approve Budgets',
        ],
        'consignments' => [
            'consignments.view' => 'View Consignments',
            'consignments.manage' => 'Manage Consignments',
            'consignments.settle' => 'Settle Consignments',
        ],
        'stock' => [
            'stock.view' => 'View Stock Takes',
            'stock.create' => 'Create Stock Takes',
            'stock.transfers.view' => 'View Stock Transfers',
            'stock.transfers.manage' => 'Manage Stock Transfers',
        ],
        'banking' => [
            'banking.view' => 'View Banking',
            'banking.manage' => 'Manage Bank Accounts & Transactions',
            'banking.reconcile' => 'Reconcile Bank Statements',
        ],
        'assets' => [
            'assets.view' => 'View Assets',
            'assets.manage' => 'Manage Assets',
        ],
        'expenses' => [
            'expenses.view' => 'View Expenses',
            'expenses.manage' => 'Manage Expenses & Bills',
        ],
        'commissions' => [
            'commissions.view' => 'View Commissions',
            'commissions.manage' => 'Manage Commissions',
        ],
        'documents' => [
            'documents.view' => 'View Document Templates',
            'documents.manage' => 'Manage Document Templates',
        ],
        'audit' => [
            'audit.view' => 'View Audit Logs',
            'audit.manage' => 'Manage Audit Settings',
        ],
        'notifications' => [
            'notifications.view' => 'View Notifications',
            'notifications.manage' => 'Manage Notification Settings',
        ],
        'settings' => [
            'settings.view' => 'View Settings',
            'settings.manage' => 'Manage Settings',
            'settings.company' => 'Manage Company Settings',
            'settings.users' => 'Manage Users & Permissions',
        ],
        'surgery' => [
            'surgery.view' => 'View Surgery Usage',
            'surgery.manage' => 'Manage Surgery Usage',
        ],
        'packages' => [
            'packages.view' => 'View Packages',
            'packages.manage' => 'Manage Packages',
        ],
        'refunds' => [
            'refunds.view' => 'View Refunds',
            'refunds.process' => 'Process Refunds',
        ],
    ],

    /**
     * Default permissions for each role
     * These are pre-ticked when a new staff member is created
     * Admins can remove these defaults and/or add additional permissions
     */
    'defaults' => [
        'staff' => [
            'inventory.view',
            'pos.access',
            'sales.view',
            'customers.view',
            'orders.create',
            'rentals.view',
            'stock.view',
            'stock.transfers.view',
            'notifications.view',
            'surgery.view',
            'settings.view',
        ],
        'accountant' => [
            'inventory.view',
            'sales.view',
            'reports.view',
            'finance.view',
            'accounting.view',
            'accounting.reports',
            'payroll.view',
            'suppliers.view',
            'customers.view',
            'expenses.view',
            'banking.view',
            'stock.view',
            'notifications.view',
        ],
    ],

    /**
     * Permission categories that are considered "admin-level"
     * These can be granted to staff but should be highlighted in UI
     */
    'admin_level' => [
        'accounting.manage',
        'accounting.journal',
        'accounting.periods',
        'payroll.process',
        'payroll.deductions',
        'payroll.reimbursements',
        'orders.approve',
        'budgets.manage',
        'budgets.approve',
        'audit.view',
        'audit.manage',
        'settings.manage',
        'settings.company',
        'settings.users',
        'staff.view',
        'staff.manage',
    ],
];
