import type { AccountType, DetailLayout } from './types';

// Single source of truth for which detail layout a product gets.
// Mirror this in Laravel as `Product::detailLayout()` so the backend can
// emit it as a computed attribute if it ever needs to.
const ACCOUNT_TYPE_TO_LAYOUT: Record<AccountType, DetailLayout> = {
  verified:           'simple_two',     // shop-details-2.html
  inactive_exclusive: 'rich',           // shop-details.html
  nfa:                'simple_two',     // shop-details-2.html
  nfa_inactive:       'fortnite_four',  // shop-details-4.html
  standard:           'simple_three',   // shop-details-3.html
};

export function accountTypeToLayout(accountType: AccountType): DetailLayout {
  return ACCOUNT_TYPE_TO_LAYOUT[accountType];
}
