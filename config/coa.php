<?php

// config/coa.php

return [
    [
        'name' => 'Asset',
        'code' => '1',
        'type' => 'group',
        'children' => [
            [
                'name' => 'Current Asset',
                'code' => '1.1',
                'type' => 'group',
                'children' => [
                    [
                        'name' => 'Cash',
                        'code' => '1.1.1',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Cash in Hand', 'code' => '1.1.1.1', 'type' => 'ledger'],
                        ],
                    ],
                    [
                        'name' => 'Cash at Bank',
                        'code' => '1.1.2',
                        'type' => 'group',
                        'children' => [
                            [
                                'name' => 'Bank A/C-Current',
                                'code' => '1.1.2.1',
                                'type' => 'group',
                                'children' => [
                                    ['name' => 'Main Bank Account', 'code' => '1.1.2.1.1', 'type' => 'ledger'],
                                ],
                            ],
                            ['name' => 'Bank A/C-Saving',   'code' => '1.1.2.2', 'type' => 'group'],
                        ],
                    ],
                    [
                        'name' => 'Account Receivable',
                        'code' => '1.1.4',
                        'type' => 'group',
                        'children' => [
                            [
                                'name' => 'Customer Receivable',
                                'code' => '1.1.4.1',
                                'type' => 'group',
                                'children' => [
                                    ['name' => 'Accounts Receivable', 'code' => '1.1.4.1.1', 'type' => 'ledger'],
                                ],
                            ],
                        ]
                    ],
                    [
                        'name' => 'Inventory',
                        'code' => '1.1.5',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Inventory Ledger', 'code' => '1.1.5.1', 'type' => 'ledger'],
                        ],
                    ],
                    ['name' => 'Short-Term Investments',    'code' => '1.1.6', 'type' => 'group'],
                    ['name' => 'Prepaid Expenses',          'code' => '1.1.7', 'type' => 'group'],
                    ['name' => 'Loans & Advances',          'code' => '1.1.8', 'type' => 'group'],
                    [
                        'name' => 'Other Current Assets',
                        'code' => '1.1.9',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Input VAT Receivable', 'code' => '1.1.9.1', 'type' => 'ledger'],
                            ['name' => 'AIT Receivable', 'code' => '1.1.9.2', 'type' => 'ledger'],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Non-Current Asset',
                'code' => '1.2',
                'type' => 'group',
                'children' => [
                    [
                        'name' => 'Fixed/Tangible Assets',
                        'code' => '1.2.1',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Land',                        'code' => '1.2.1.1',  'type' => 'group'],
                            ['name' => 'Building',                    'code' => '1.2.1.2',  'type' => 'group'],
                            ['name' => 'Furniture & Fixtures',        'code' => '1.2.1.3',  'type' => 'group'],
                            ['name' => 'Office Equipment',            'code' => '1.2.1.4',  'type' => 'group'],
                            ['name' => 'Computers & IT Equipment',    'code' => '1.2.1.5',  'type' => 'group'],
                            ['name' => 'Vehicles',                    'code' => '1.2.1.6',  'type' => 'group'],
                            ['name' => 'Plant & Machinery',           'code' => '1.2.1.7',  'type' => 'group'],
                            ['name' => 'Generator',                   'code' => '1.2.1.8',  'type' => 'group'],
                            ['name' => 'Software / Intangible Assets','code' => '1.2.1.9',  'type' => 'group'],
                            ['name' => 'Accumulated Depreciation',    'code' => '1.2.1.10', 'type' => 'group'],
                        ],
                    ],
                    [
                        'name' => 'Intangible Assets',
                        'code' => '1.2.2',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Trademark',            'code' => '1.2.2.1', 'type' => 'group'],
                            ['name' => 'Patent & Copyright',   'code' => '1.2.2.2', 'type' => 'group'],
                        ],
                    ],
                    ['name' => 'Long-term Investments',    'code' => '1.2.3', 'type' => 'group'],
                    ['name' => 'Other Non-Current Assets', 'code' => '1.2.4', 'type' => 'group'],
                ],
            ],
        ],
    ],

    [
        'name' => 'Liability',
        'code' => '2',
        'type' => 'group',
        'children' => [
            [
                'name' => 'Current Liability',
                'code' => '2.1',
                'type' => 'group',
                'children' => [
                    [
                        'name' => 'A/C Payable',
                        'code' => '2.1.1',
                        'type' => 'group',
                        'children' => [
                            [
                                'name' => 'Vendor Payable',
                                'code' => '2.1.1.1',
                                'type' => 'group',
                                'children' => [
                                    ['name' => 'Accounts Payable', 'code' => '2.1.1.1.1', 'type' => 'ledger'],
                                ],
                            ],
                        ]
                    ],
                    ['name' => 'Short-term Loan',                     'code' => '2.1.2', 'type' => 'group'],
                    ['name' => 'Accrued Expenses',                    'code' => '2.1.3', 'type' => 'group'],
                    ['name' => 'Credit Card',                         'code' => '2.1.4', 'type' => 'group'],
                    ['name' => 'Unearned Revenue',                    'code' => '2.1.5', 'type' => 'group'],
                    ['name' => 'Provisions',                          'code' => '2.1.6', 'type' => 'group'],
                    ['name' => 'Current Portion of Long-term Debt',   'code' => '2.1.7', 'type' => 'group'],
                    ['name' => 'Other Current Liabilities',           'code' => '2.1.8', 'type' => 'group'],
                    ['name' => 'Tax Payable',                         'code' => '2.1.9', 'type' => 'ledger'],
                ],
            ],
            [
                'name' => 'Non-Current Liability',
                'code' => '2.2',
                'type' => 'group',
                'children' => [
                    ['name' => 'Long-term Loans',              'code' => '2.2.1', 'type' => 'group'],
                    ['name' => 'Bonds Payable',                'code' => '2.2.2', 'type' => 'group'],
                    ['name' => 'Provisions',                   'code' => '2.2.3', 'type' => 'group'],
                    ['name' => 'Deferred Tax Liabilities',     'code' => '2.2.4', 'type' => 'group'],
                    ['name' => 'Other Non-Current Liabilities','code' => '2.2.5', 'type' => 'group'],
                ],
            ],
        ],
    ],

    [
        'name' => 'Equity',
        'code' => '3',
        'type' => 'group',
        'children' => [
            ['name' => "Owner's Capital",     'code' => '3.1', 'type' => 'group'],
            ['name' => "Owner's Drawings",    'code' => '3.2', 'type' => 'group'],
            ['name' => 'Retained Earnings',   'code' => '3.3', 'type' => 'group'],
            ['name' => 'Share Capital',       'code' => '3.4', 'type' => 'group'],
            ['name' => 'Reserves & Surplus',  'code' => '3.5', 'type' => 'group'],
            ['name' => 'Others Equity',       'code' => '3.6', 'type' => 'group'],
        ],
    ],

    [
        'name' => 'Income',
        'code' => '4',
        'type' => 'group',
        'children' => [
            [
                'name' => 'Operating Income',
                'code' => '4.1',
                'type' => 'group',
                'children' => [
                    [
                        'name' => 'Sales Revenue',
                        'code' => '4.1.1',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Sales Revenue Ledger', 'code' => '4.1.1.1', 'type' => 'ledger'],
                        ],
                    ],
                    ['name' => 'Service Revenue',               'code' => '4.1.2', 'type' => 'group'],
                    ['name' => 'Commission income (operating)', 'code' => '4.1.3', 'type' => 'group'],
                ],
            ],
            [
                'name' => 'Non-operating Income',
                'code' => '4.2',
                'type' => 'group',
                'children' => [
                    ['name' => 'Interest Income',               'code' => '4.2.1', 'type' => 'group'],
                    ['name' => 'Dividend Income',               'code' => '4.2.2', 'type' => 'group'],
                    ['name' => 'Gain on Sale of Fixed Assets',  'code' => '4.2.3', 'type' => 'group'],
                    ['name' => 'Rental Income',                 'code' => '4.2.4', 'type' => 'group'],
                    ['name' => 'Commission Income',             'code' => '4.2.5', 'type' => 'group'],
                    ['name' => 'Investment Income',             'code' => '4.2.6', 'type' => 'group'],
                ],
            ],
            [
                'name' => 'Other Income',
                'code' => '4.3',
                'type' => 'group',
                'children' => [
                    [
                        'name' => 'Miscellaneous',
                        'code' => '4.3.1',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Purchase Discount Earned', 'code' => '4.3.1.1', 'type' => 'ledger'],
                        ],
                    ],
                    ['name' => 'Scrap / Sale',                  'code' => '4.3.2', 'type' => 'group'],
                    ['name' => 'Penalty',                       'code' => '4.3.3', 'type' => 'group'],
                    ['name' => 'Donation income',               'code' => '4.3.4', 'type' => 'group'],
                ],
            ],
        ],
    ],

    [
        'name' => 'Expenses',
        'code' => '5',
        'type' => 'group',
        'children' => [
            [
                'name' => 'Direct Expenses',
                'code' => '5.1',
                'type' => 'group',
                'children' => [
                    [
                        'name' => 'Cost of Goods Sold',
                        'code' => '5.1.1',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Purchase / COGS', 'code' => '5.1.1.1', 'type' => 'ledger'],
                        ],
                    ],
                    ['name' => 'Raw Material Consumed',              'code' => '5.1.2',  'type' => 'group'],
                    ['name' => 'Direct Labor & Wages',               'code' => '5.1.3',  'type' => 'group'],
                    ['name' => 'Purchase Commission',                'code' => '5.1.4',  'type' => 'group'],
                    ['name' => 'Freight In',                         'code' => '5.1.5',  'type' => 'group'],
                    ['name' => 'Loading & Unloading Charges',        'code' => '5.1.6',  'type' => 'group'],
                    ['name' => 'Packaging Material',                 'code' => '5.1.7',  'type' => 'group'],
                    ['name' => 'Production Factory Rent',            'code' => '5.1.8',  'type' => 'group'],
                    ['name' => 'Production Utility',                 'code' => '5.1.9',  'type' => 'group'],
                    ['name' => 'Production Machinery Depreciation',  'code' => '5.1.10', 'type' => 'group'],
                ],
            ],
            [
                'name' => 'Indirect Expenses',
                'code' => '5.2',
                'type' => 'group',
                'children' => [
                    ['name' => 'Office Rent',                        'code' => '5.2.1',  'type' => 'group'],
                    [
                        'name' => 'Salaries & Wages',
                        'code' => '5.2.2',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Salary Expense', 'code' => '5.2.2.1', 'type' => 'ledger'],
                        ],
                    ],
                    ['name' => 'Utilities',                          'code' => '5.2.3',  'type' => 'group'],
                    ['name' => 'Office Supplies',                    'code' => '5.2.4',  'type' => 'group'],
                    ['name' => 'Repair & Maintenance',               'code' => '5.2.5',  'type' => 'group'],
                    ['name' => 'Telephone & Mobile Bill',            'code' => '5.2.6',  'type' => 'group'],
                    ['name' => 'Insurance',                          'code' => '5.2.7',  'type' => 'group'],
                    ['name' => 'Legal & Professional Fees',          'code' => '5.2.8',  'type' => 'group'],
                    ['name' => 'Depreciation (Office Equipment)',    'code' => '5.2.9',  'type' => 'group'],
                    ['name' => 'Meals & Entertainment (Office)',     'code' => '5.2.10', 'type' => 'group'],
                    ['name' => 'Postage & Courier',                  'code' => '5.2.11', 'type' => 'group'],
                    ['name' => 'Advertisement & Promotion',          'code' => '5.2.12', 'type' => 'group'],
                    ['name' => 'Transportation / Delivery Charges',  'code' => '5.2.13', 'type' => 'group'],
                    [
                        'name' => 'Discounts Allowed',
                        'code' => '5.2.14',
                        'type' => 'group',
                        'children' => [
                            ['name' => 'Discount Allowed Expense', 'code' => '5.2.14.1', 'type' => 'ledger'],
                        ],
                    ],
                    ['name' => 'Interest on Loan',                   'code' => '5.2.15', 'type' => 'group'],
                    ['name' => 'Bank Charges',                       'code' => '5.2.16', 'type' => 'group'],
                    ['name' => 'Audit fees',                         'code' => '5.2.17', 'type' => 'group'],
                    ['name' => 'Miscellaneous',                      'code' => '5.2.18', 'type' => 'group'],
                ],
            ],
        ],
    ],
];
