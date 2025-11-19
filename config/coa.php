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
          ['name' => 'Cash',                      'code' => '1.1.1', 'type' => 'ledger'],
          ['name' => 'Bank A/C-Current',          'code' => '1.1.2', 'type' => 'ledger'],
          ['name' => 'Bank A/C-Saving',           'code' => '1.1.3', 'type' => 'ledger'],
          ['name' => 'Account Receivable',        'code' => '1.1.4', 'type' => 'ledger'],
          ['name' => 'Inventory',                 'code' => '1.1.5', 'type' => 'ledger'],
          ['name' => 'Short-Term Investments',    'code' => '1.1.6', 'type' => 'ledger'],
          ['name' => 'Prepaid Expenses',          'code' => '1.1.7', 'type' => 'ledger'],
          ['name' => 'Loans & Advances',          'code' => '1.1.8', 'type' => 'ledger'],
          ['name' => 'Other Current Assets',      'code' => '1.1.9', 'type' => 'ledger'],
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
              ['name' => 'Land',                        'code' => '1.2.1.1', 'type' => 'ledger'],
              ['name' => 'Building',                    'code' => '1.2.1.2', 'type' => 'ledger'],
              ['name' => 'Furniture & Fixtures',        'code' => '1.2.1.3', 'type' => 'ledger'],
              ['name' => 'Office Equipment',            'code' => '1.2.1.4', 'type' => 'ledger'],
              ['name' => 'Computers & IT Equipment',    'code' => '1.2.1.5', 'type' => 'ledger'],
              ['name' => 'Vehicles',                    'code' => '1.2.1.6', 'type' => 'ledger'],
              ['name' => 'Plant & Machinery',           'code' => '1.2.1.7', 'type' => 'ledger'],
              ['name' => 'Generator',                   'code' => '1.2.1.8', 'type' => 'ledger'],
              ['name' => 'Software / Intangible Assets','code' => '1.2.1.9', 'type' => 'ledger'],
              ['name' => 'Accumulated Depreciation',    'code' => '1.2.1.10','type' => 'ledger'],
            ],
          ],
          [
            'name' => 'Intangible Assets',
            'code' => '1.2.2',
            'type' => 'group',
            'children' => [
              ['name' => 'Trademark',            'code' => '1.2.2.1', 'type' => 'ledger'],
              ['name' => 'Patent & Copyright',   'code' => '1.2.2.2', 'type' => 'ledger'],
            ],
          ],
          ['name' => 'Long-term Investments',    'code' => '1.2.3', 'type' => 'ledger'],
          ['name' => 'Other Non-Current Assets', 'code' => '1.2.4', 'type' => 'ledger'],
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
          ['name' => 'A/C Payable',                         'code' => '2.1.1', 'type' => 'ledger'],
          ['name' => 'Short-term Loan',                     'code' => '2.1.2', 'type' => 'ledger'],
          ['name' => 'Accrued Expenses',                    'code' => '2.1.3', 'type' => 'ledger'],
          ['name' => 'Credit Card',                         'code' => '2.1.4', 'type' => 'ledger'],
          ['name' => 'Unearned Revenue',                    'code' => '2.1.5', 'type' => 'ledger'],
          ['name' => 'Provisions',                          'code' => '2.1.6', 'type' => 'ledger'],
          ['name' => 'Current Portion of Long-term Debt',   'code' => '2.1.7', 'type' => 'ledger'],
          ['name' => 'Other Current Liabilities',           'code' => '2.1.8', 'type' => 'ledger'],
        ],
      ],
      [
        'name' => 'Non-Current Liability',
        'code' => '2.2',
        'type' => 'group',
        'children' => [
          ['name' => 'Long-term Loans',         'code' => '2.2.1', 'type' => 'ledger'],
          ['name' => 'Bonds Payable',           'code' => '2.2.2', 'type' => 'ledger'],
          ['name' => 'Provisions',              'code' => '2.2.3', 'type' => 'ledger'],
          ['name' => 'Deferred Tax Liabilities','code' => '2.2.4', 'type' => 'ledger'],
          ['name' => 'Other Non-Current Liabilities','code' => '2.2.5','type'=>'ledger'],
        ],
      ],
    ],
  ],

  [
    'name' => 'Equity',
    'code' => '3',
    'type' => 'group',
    'children' => [
      ['name' => "Owner's Capital",     'code' => '3.1', 'type' => 'ledger'],
      ['name' => "Owner's Drawings",    'code' => '3.2', 'type' => 'ledger'],
      ['name' => 'Retained Earnings',   'code' => '3.3', 'type' => 'ledger'],
      ['name' => 'Share Capital',       'code' => '3.4', 'type' => 'ledger'],
      ['name' => 'Reserves & Surplus',  'code' => '3.5', 'type' => 'ledger'],
      ['name' => 'Others Equity',       'code' => '3.6', 'type' => 'ledger'],
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
          ['name' => 'Sales Revenue',                  'code' => '4.1.1', 'type' => 'ledger'],
          ['name' => 'Service Revenue',                'code' => '4.1.2', 'type' => 'ledger'],
          ['name' => 'Commission income (operating)',  'code' => '4.1.3', 'type' => 'ledger'],
        ],
      ],
      [
        'name' => 'Non-operating Income',
        'code' => '4.2',
        'type' => 'group',
        'children' => [
          ['name' => 'Interest Income',                'code' => '4.2.1', 'type' => 'ledger'],
          ['name' => 'Dividend Income',                'code' => '4.2.2', 'type' => 'ledger'],
          ['name' => 'Gain on Sale of Fixed Assets',   'code' => '4.2.3', 'type' => 'ledger'],
          ['name' => 'Rental Income',                  'code' => '4.2.4', 'type' => 'ledger'],
          ['name' => 'Commission Income',              'code' => '4.2.5', 'type' => 'ledger'],
          ['name' => 'Investment Income',              'code' => '4.2.6', 'type' => 'ledger'],
        ],
      ],
      [
        'name' => 'Other Income',
        'code' => '4.3',
        'type' => 'group',
        'children' => [
          ['name' => 'Miscellaneous',                  'code' => '4.3.1', 'type' => 'ledger'],
          ['name' => 'Scrap / Sale',                   'code' => '4.3.2', 'type' => 'ledger'],
          ['name' => 'Penalty',                        'code' => '4.3.3', 'type' => 'ledger'],
          ['name' => 'Donation income',                'code' => '4.3.4', 'type' => 'ledger'],
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
          ['name' => 'Cost of Goods Sold',                 'code' => '5.1.1',  'type' => 'ledger'],
          ['name' => 'Raw Material Consumed',              'code' => '5.1.2',  'type' => 'ledger'],
          ['name' => 'Direct Labor & Wages',               'code' => '5.1.3',  'type' => 'ledger'],
          ['name' => 'Purchase Commission',                'code' => '5.1.4',  'type' => 'ledger'],
          ['name' => 'Freight In',                         'code' => '5.1.5',  'type' => 'ledger'],
          ['name' => 'Loading & Unloading Charges',        'code' => '5.1.6',  'type' => 'ledger'],
          ['name' => 'Packaging Material',                 'code' => '5.1.7',  'type' => 'ledger'],
          ['name' => 'Production Factory Rent',            'code' => '5.1.8',  'type' => 'ledger'],
          ['name' => 'Production Utility',                 'code' => '5.1.9',  'type' => 'ledger'],
          ['name' => 'Production Machinery Depreciation',  'code' => '5.1.10', 'type' => 'ledger'],
        ],
      ],
      [
        'name' => 'Indirect Expenses',
        'code' => '5.2',
        'type' => 'group',
        'children' => [
          ['name' => 'Office Rent',                        'code' => '5.2.1',  'type' => 'ledger'],
          ['name' => 'Salaries & Wages',                   'code' => '5.2.2',  'type' => 'ledger'],
          ['name' => 'Utilities',                          'code' => '5.2.3',  'type' => 'ledger'],
          ['name' => 'Office Supplies',                    'code' => '5.2.4',  'type' => 'ledger'],
          ['name' => 'Repair & Maintenance',               'code' => '5.2.5',  'type' => 'ledger'],
          ['name' => 'Telephone & Mobile Bill',            'code' => '5.2.6',  'type' => 'ledger'],
          ['name' => 'Insurance',                          'code' => '5.2.7',  'type' => 'ledger'],
          ['name' => 'Legal & Professional Fees',          'code' => '5.2.8',  'type' => 'ledger'],
          ['name' => 'Depreciation (Office Equipment)',    'code' => '5.2.9',  'type' => 'ledger'],
          ['name' => 'Meals & Entertainment (Office)',     'code' => '5.2.10', 'type' => 'ledger'],
          ['name' => 'Postage & Courier',                  'code' => '5.2.11', 'type' => 'ledger'],
          ['name' => 'Advertisement & Promotion',          'code' => '5.2.12', 'type' => 'ledger'],
          ['name' => 'Transportation / Delivery Charges',  'code' => '5.2.13', 'type' => 'ledger'],
          ['name' => 'Discounts Allowed',                  'code' => '5.2.14', 'type' => 'ledger'],
          ['name' => 'Interest on Loan',                   'code' => '5.2.15', 'type' => 'ledger'],
          ['name' => 'Bank Charges',                       'code' => '5.2.16', 'type' => 'ledger'],
          ['name' => 'Audit fees',                         'code' => '5.2.17', 'type' => 'ledger'],
          ['name' => 'Miscellaneous',                      'code' => '5.2.18', 'type' => 'ledger'],
        ],
      ],
    ],
  ],
];

