export default [
    {
        path: "/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "/admin/accounting/chart-of-accounts",
                component: () =>
                    import(
                        "../views/accounting/chart-of-accounts/index.vue"
                    ),
                name: "admin.accounting.coa.index",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_coa",
                },
            },
            {
                path: "/admin/accounting/journal-entries",
                component: () =>
                    import(
                        "../views/accounting/journal-entries/index.vue"
                    ),
                name: "admin.accounting.journal.index",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_journal",
                },
            },
            {
                path: "/admin/accounting/reports/trial-balance",
                component: () =>
                    import(
                        "../views/accounting/reports/trial-balance.vue"
                    ),
                name: "admin.accounting.trial_balance",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_trial_balance",
                },
            },
            {
                path: "/admin/accounting/reports/profit-loss",
                component: () =>
                    import(
                        "../views/accounting/reports/profit-loss.vue"
                    ),
                name: "admin.accounting.profit_loss",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_profit_loss",
                },
            },
            {
                path: "/admin/accounting/reports/balance-sheet",
                component: () =>
                    import(
                        "../views/accounting/reports/balance-sheet.vue"
                    ),
                name: "admin.accounting.balance_sheet",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_balance_sheet",
                },
            },
            {
                path: "/admin/accounting/reports/general-ledger",
                component: () =>
                    import(
                        "../views/accounting/reports/general-ledger.vue"
                    ),
                name: "admin.accounting.general_ledger",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_general_ledger",
                },
            },
            {
                path: "/admin/accounting/reports/customer-ledger",
                component: () =>
                    import(
                        "../views/accounting/reports/customer-ledger.vue"
                    ),
                name: "admin.accounting.customer_ledger",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_customer_ledger",
                },
            },
            {
                path: "/admin/accounting/reports/supplier-ledger",
                component: () =>
                    import(
                        "../views/accounting/reports/supplier-ledger.vue"
                    ),
                name: "admin.accounting.supplier_ledger",
                meta: {
                    requireAuth: true,
                    menuParent: "accounting",
                    menuKey: () => "accounting_supplier_ledger",
                },
            },
        ],
    },
];
