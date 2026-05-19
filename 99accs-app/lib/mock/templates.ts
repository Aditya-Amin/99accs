// Layout-specific detail-page data shared across every product of the same
// account_type. Mirrors what a Laravel "join with template" query would
// return — keeps the catalog JSON lean (every shop-card row stays small)
// while every detail-page read still ships its full rich payload.
//
// Detail layout is derived from `account_type` via `accountTypeToLayout()`.
// This file maps each layout to a preset bundle and exposes
// `applyDetailTemplate(product)` which the mock's `getMockProduct(slug)`
// calls before returning the detail row.

import type {
  Product,
  ProductAgentEntry,
  ProductProfileInfo,
  ProductDescriptionSection,
  ProductGuarantee,
  ProductLevel,
  ProductStat,
  ProductLocker,
  ProductSeasons,
  ProductSkinInventory,
  ProductSkinItem,
  ProductBuddyInventory,
  ProductSkinFilters,
} from '@/lib/api/types';
import { accountTypeToLayout } from '@/lib/api/layout';

const GUARANTEE: ProductGuarantee = {
  title: '99Accs Guarantee',
  body: 'Your purchase made on the 99Accs platform are protected by us.',
};

// "Buy Valorant Account" 6-section block + FAQ — used on shop-details.html
// (rich / valorant inactive_exclusive) and shop-details-4.html (fortnite_four
// / fortnite nfa_inactive). The two pages share an identical description block.
export const VALORANT_BUY_DESCRIPTION_SECTIONS: ProductDescriptionSection[] = [
  {
    heading: 'Buy Valorant Account — High Ranks and Rare Skins',
    type: 'paragraph',
    items: [
      'Want to buy a Valorant account and start playing at a higher level? Get instant access to accounts with high ranks, rare weapon skins, agents, and VP — no need to grind from scratch.',
    ],
  },
  {
    heading: 'Benefits of Valorant Account with Progress',
    type: 'list',
    items: [
      'High competitive ranks (Platinum, Diamond, Immortal, Radiant)',
      'Rare and expensive weapon skins, including premium collection knives',
      'Unlocked agents for instant play',
      'Valorant Points (VP) for in-game purchases',
      'Save hundreds of hours on rank grinding and collection building',
    ],
  },
  {
    heading: "What's Included in Valorant Account",
    type: 'list',
    items: [
      'Collection of weapon skins, including knives and premium sets (according to account description)',
      'Unlocked agents',
      'Competitive mode progress and rank history',
      'Valorant Points for purchases',
      'Ready to play immediately after purchase',
    ],
  },
  {
    heading: 'Our Advantages',
    type: 'list',
    items: [
      'Account verification before sale',
      'Instant delivery of credentials in personal account after purchase',
      'Multiple payment methods: cards, SBP, YooMoney, and others',
      '24/7 support',
    ],
  },
  {
    heading: 'How to Purchase a Valorant Account',
    type: 'list',
    list_class: 'started-list',
    items: [
      'Choose an account with desired rank and skins',
      'Top up your personal account balance with a convenient method',
      'Place an order (debit from balance)',
      'Receive login credentials immediately after purchase',
      'Change password and set up two-factor authentication',
    ],
  },
  {
    heading: 'Frequently Asked Questions',
    type: 'faq',
    items: [
      {
        question: 'What ranks are available in Valorant accounts?',
        answer:
          'We have accounts with various ranks: from Iron to Radiant. Each account card shows the current rank, peak rank achieved, and account region.',
      },
      {
        question: 'How fast is the credentials delivery?',
        answer:
          "Login credentials appear in your personal account immediately after purchase. You just need to top up your balance and place an order — you'll get account access instantly.",
      },
      {
        question: 'Can I use the account in any region?',
        answer:
          'Each account is tied to a specific region (EU, NA, AP, etc.). The region is specified in the account description. Make sure to choose an account with your preferred region for comfortable gameplay.',
      },
    ],
  },
];

// Fortnite-themed 6-section block — used on shop-details-2.html (simple_two:
// valorant verified, fortnite nfa_random, fortnite nfa_guaranteed) and
// shop-details-3.html (simple_three: legends standard).
export const FORTNITE_BUY_DESCRIPTION_SECTIONS: ProductDescriptionSection[] = [
  {
    heading: 'Description',
    type: 'paragraph',
    items: [
      "Step up your Fortnite game with this incredible account! Packed with exclusive features and epic customization options, it's your ultimate gateway to standout gameplay and style.",
    ],
  },
  {
    heading: "What's Inside:",
    type: 'list',
    items: [
      '🎯 Guaranteed Skin – Instantly enhance your Fortnite look with a featured skin to set you apart.',
      '🎁 Up to 300 Skins – Unlock an extensive collection with a ⭐️ chance to score rare OG skins.',
      '🏆 Unique Account Features – Enjoy a mix of random levels, victories, and pickaxes to personalize your gaming experience.',
      '📩 Quick Email Delivery – Securely receive your account details within minutes.',
    ],
  },
  {
    heading: 'Why This Account Stands Out:',
    type: 'list',
    items: [
      '✅ 🔒 Full Email Access – Gain full control with login credentials for both the account and its linked email.',
      '✅ 📜 Safe and Trusted Accounts – Play with confidence using accounts verified for safety and reliability.',
      '✅ 🌟 Exclusive OG Items – Discover rare skins and legendary items to make your account truly unique.',
    ],
  },
  {
    heading: 'Getting Started is Easy:',
    type: 'list',
    items: [
      'Complete your purchase and receive your login information instantly.',
      'Follow our simple setup guide to access your new account.',
      "Download Fortnite for free via the Epic Games Store if you don't have it already.",
    ],
  },
  {
    heading: 'Why Fortnite Gamers Love This Offer:',
    type: 'list',
    items: [
      'Choose an account with desired rank and skins',
      'Top up your personal account balance with a convenient method',
      'Place an order (debit from balance)',
      'Receive login credentials immediately after purchase',
      'Change password and set up two-factor authentication',
    ],
  },
  {
    heading: 'Why Fortnite Gamers Love This Offer:',
    type: 'list',
    items: [
      "Fortnite's exhilarating gameplay, creative options, and iconic skins have captured hearts worldwide. With up to 300 skins and rare OG items in your arsenal, this premium account lets you shine like never before.",
      '❓ Need assistance? Our support team is ready to help! Visit our Contact Us page for guidance.',
      "🌟 Don't Wait! Seize this chance to own a legendary Fortnite account and rule the game with unmatched style. 🎮🔥",
    ],
  },
];

// 26-tile Valorant agent inventory (mirrors shop-details.html `.shop__details-agent-nav`)
export const VALORANT_AGENTS: ProductAgentEntry[] = [
  { id: 'agent01', image: '/img/images/tab_img_01.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent02', image: '/img/images/tab_img_02.png', role_icon: '/img/icons/agent_tab_icon01.svg' },
  { id: 'agent03', image: '/img/images/tab_img_03.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent04', image: '/img/images/tab_img_04.png', role_icon: null },
  { id: 'agent05', image: '/img/images/tab_img_05.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent06', image: '/img/images/tab_img_06.png', role_icon: null },
  { id: 'agent07', image: '/img/images/tab_img_07.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent08', image: '/img/images/tab_img_08.png', role_icon: null },
  { id: 'agent09', image: '/img/images/tab_img_09.png', role_icon: null },
  { id: 'agent10', image: '/img/images/tab_img_10.png', role_icon: '/img/icons/agent_tab_icon01.svg' },
  { id: 'agent11', image: '/img/images/tab_img_11.png', role_icon: null },
  { id: 'agent12', image: '/img/images/tab_img_12.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent13', image: '/img/images/tab_img_13.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent14', image: '/img/images/tab_img_14.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent15', image: '/img/images/tab_img_15.png', role_icon: null },
  { id: 'agent16', image: '/img/images/tab_img_16.png', role_icon: null },
  { id: 'agent17', image: '/img/images/tab_img_17.png', role_icon: '/img/icons/agent_tab_icon01.svg' },
  { id: 'agent18', image: '/img/images/tab_img_18.png', role_icon: null },
  { id: 'agent19', image: '/img/images/tab_img_19.png', role_icon: null },
  { id: 'agent20', image: '/img/images/tab_img_20.png', role_icon: '/img/icons/agent_tab_icon02.svg' },
  { id: 'agent21', image: '/img/images/tab_img_21.png', role_icon: null },
  { id: 'agent22', image: '/img/images/tab_img_22.png', role_icon: null },
  { id: 'agent23', image: '/img/images/tab_img_23.png', role_icon: null },
  { id: 'agent24', image: '/img/images/tab_img_24.png', role_icon: '/img/icons/agent_tab_icon01.svg' },
  { id: 'agent25', image: '/img/images/tab_img_23.png', role_icon: null },
  { id: 'agent26', image: '/img/images/tab_img_24.png', role_icon: '/img/icons/agent_tab_icon01.svg' },
];

export const VALORANT_PROFILE_INFO: ProductProfileInfo = {
  region: 'Asia Pacific',
  region_icon: '/img/icons/server_icon.svg',
  profile_image: '/img/images/profile_img.jpg',
  profile_stats: [
    { icon: '/img/icons/profile_icon01.svg', value: '48' },
    { icon: '/img/icons/profile_icon02.svg', value: '25' },
    { icon: '/img/icons/profile_icon03.svg', value: '8500' },
  ],
  inventory_value: {
    label: 'Inventory value',
    value: '~35800 VP',
    icon: '/img/icons/valorant.svg',
  },
  ranks: [
    { image: '/img/icons/profile_rank01.png', title: 'Gold 1', label: 'Current rank - V26 ACT II' },
    { image: '/img/icons/profile_rank02.png', title: 'Silver 3', label: 'Previous act rank - V25 ACT V' },
    { image: '/img/icons/profile_rank03.png', title: 'Gold 2', label: 'Maximum rank - V25 ACT II' },
  ],
  features: [
    { icon: 'mail', title: 'Mail Access' },
    { icon: 'clock', title: 'Last Active 31.03.2026' },
    { icon: 'phone', title: 'Phone Number Linked', red: true },
  ],
};

// Valorant skin + buddies inventory — used by shop-details.html (rich /
// inactive_exclusive). Items are seeded to make the Rarity + Weapon Type
// filters meaningful (every rarity + every weapon type has ≥1 entry), and
// the sidebar counts are derived rather than pre-baked so the data stays
// consistent if items[] is edited.
const VALORANT_SKIN_ITEMS: ProductSkinItem[] = [
  // Melee — knives, baseball bats. Mostly the `exclusive` (HTML icon02) tier.
  { id: 'sk01', name: 'Champions 2022 Butterfly Knife', image: '/img/valorant/skin_item_01.png', rarity: 'exclusive', weapon_type: 'melee', levels: 3 },
  { id: 'sk02', name: 'Radiant Crisis 001 Baseball Bat', image: '/img/valorant/skin_item_02.png', rarity: 'exclusive', weapon_type: 'melee', levels: 2 },
  { id: 'sk03', name: 'Xenohunter Knife', image: '/img/valorant/skin_item_03.png', rarity: 'exclusive', weapon_type: 'melee', levels: 3 },
  { id: 'sk04', name: 'Reaver Karambit', image: '/img/valorant/skin_item_01.png', rarity: 'ultra', weapon_type: 'melee', levels: 4 },

  // Rifles — Vandal, Phantom, Bulldog. Mix of exclusive + premium.
  {
    id: 'sk05',
    name: 'RGX 11z Pro Operator',
    image: '/img/valorant/skin_item_04.png',
    rarity: 'exclusive',
    weapon_type: 'rifles',
    levels: 5,
    color_variants: [
      { id: 'cv05a', image: '/img/valorant/skin_color_img01.jpg' },
      { id: 'cv05b', image: '/img/valorant/skin_color_img02.jpg' },
      { id: 'cv05c', image: '/img/valorant/skin_color_img03.jpg' },
      { id: 'cv05d', image: '/img/valorant/skin_color_img04.jpg' },
    ],
  },
  {
    id: 'sk06',
    name: "Gaia's Vengeance Vandal",
    image: '/img/valorant/skin_item_05.png',
    rarity: 'premium',
    weapon_type: 'rifles',
    thumb_modifier: 'two',
    color_variants: [
      { id: 'cv06a', image: '/img/valorant/skin_color_img01.jpg' },
      { id: 'cv06b', image: '/img/valorant/skin_color_img02.jpg' },
      { id: 'cv06c', image: '/img/valorant/skin_color_img03.jpg' },
      { id: 'cv06d', image: '/img/valorant/skin_color_img04.jpg' },
    ],
  },
  { id: 'sk07', name: 'Prime Vandal', image: '/img/valorant/skin_item_06.png', rarity: 'premium', weapon_type: 'rifles', levels: 4 },
  { id: 'sk08', name: 'Glitchpop Phantom', image: '/img/valorant/skin_item_07.png', rarity: 'premium', weapon_type: 'rifles', levels: 4 },
  { id: 'sk09', name: 'Reaver Phantom', image: '/img/valorant/skin_item_08.png', rarity: 'premium', weapon_type: 'rifles', levels: 4 },

  // Sniper Rifles — Operator, Marshal.
  { id: 'sk10', name: 'Elderflame Operator', image: '/img/valorant/skin_item_09.png', rarity: 'deluxe', weapon_type: 'sniper_rifles', levels: 5 },
  { id: 'sk11', name: 'Ion Operator', image: '/img/valorant/skin_item_10.png', rarity: 'deluxe', weapon_type: 'sniper_rifles', levels: 4 },
  { id: 'sk12', name: 'Sentinels of Light Marshal', image: '/img/valorant/skin_item_11.png', rarity: 'premium', weapon_type: 'sniper_rifles', levels: 3 },

  // Sidearms — Sheriff, Ghost, Frenzy.
  { id: 'sk13', name: 'Reaver Sheriff', image: '/img/valorant/skin_item_12.png', rarity: 'deluxe', weapon_type: 'sidearms', levels: 3 },
  { id: 'sk14', name: 'Prime Frenzy', image: '/img/valorant/skin_item_13.png', rarity: 'deluxe', weapon_type: 'sidearms', levels: 2 },
  { id: 'sk15', name: 'Glitchpop Classic', image: '/img/valorant/skin_item_14.png', rarity: 'select', weapon_type: 'sidearms', levels: 1 },

  // SMGs — Spectre, Stinger.
  { id: 'sk16', name: 'Glitchpop Spectre', image: '/img/valorant/skin_item_15.png', rarity: 'select', weapon_type: 'smgs', levels: 2 },
  { id: 'sk17', name: 'Reaver Stinger', image: '/img/valorant/skin_item_16.png', rarity: 'deluxe', weapon_type: 'smgs', levels: 3 },

  // Shotguns — Judge, Bucky.
  { id: 'sk18', name: 'Prime Judge', image: '/img/valorant/skin_item_01.png', rarity: 'select', weapon_type: 'shotguns', levels: 2 },

  // Machine Guns — Odin, Ares.
  { id: 'sk19', name: 'Ion Odin', image: '/img/valorant/skin_item_02.png', rarity: 'premium', weapon_type: 'machine_guns', levels: 3 },
  { id: 'sk20', name: 'Glitchpop Ares', image: '/img/valorant/skin_item_03.png', rarity: 'select', weapon_type: 'machine_guns', levels: 2 },
];

// Filter chips. Counts are derived from VALORANT_SKIN_ITEMS so the sidebar
// stays consistent with the grid as items[] evolves.
function countByWeapon(key: ProductSkinItem['weapon_type']): number {
  return VALORANT_SKIN_ITEMS.filter((s) => s.weapon_type === key).length;
}

export const VALORANT_SKIN_FILTERS: ProductSkinFilters = {
  rarities: [
    { key: 'ultra',     label: 'Ultra',     icon: '/img/icons/sidebar_icon01.svg' },
    { key: 'exclusive', label: 'Exclusive', icon: '/img/icons/sidebar_icon02.svg' },
    { key: 'premium',   label: 'Premium',   icon: '/img/icons/sidebar_icon03.svg' },
    { key: 'deluxe',    label: 'Deluxe',    icon: '/img/icons/sidebar_icon04.svg' },
    { key: 'select',    label: 'Select',    icon: '/img/icons/sidebar_icon05.svg' },
  ],
  weapon_types: [
    { key: 'melee',         label: 'Lelle',        count: countByWeapon('melee') },
    { key: 'rifles',        label: 'Rifles',       count: countByWeapon('rifles') },
    { key: 'sniper_rifles', label: 'Sniper Rifles', count: countByWeapon('sniper_rifles') },
    { key: 'sidearms',      label: 'Sidearms',     count: countByWeapon('sidearms') },
    { key: 'smgs',          label: 'SMGs',         count: countByWeapon('smgs') },
    { key: 'shotguns',      label: 'Shotguns',     count: countByWeapon('shotguns') },
    { key: 'machine_guns',  label: 'Machine Guns', count: countByWeapon('machine_guns') },
  ],
};

export const VALORANT_SKIN_INVENTORY: ProductSkinInventory = {
  total: 70,
  purchased: 17,
  vp: 35800,
  items: VALORANT_SKIN_ITEMS,
};

export const VALORANT_BUDDY_INVENTORY: ProductBuddyInventory = {
  total: 85,
  purchased: 0,
  vp: 0,
  items: [
    { id: 'b01', name: 'Ep 5 // 1 Coin',          image: '/img/valorant/buddies_img_01.png' },
    { id: 'b02', name: 'Pocket Sized Sheriff',    image: '/img/valorant/buddies_img_02.png' },
    { id: 'b03', name: 'Deep Divisions',          image: '/img/valorant/buddies_img_03.png' },
    { id: 'b04', name: 'PlayZilla Trick Master',  image: '/img/valorant/buddies_img_04.png' },
    { id: 'b05', name: 'EP 2 // 3 Coin',          image: '/img/valorant/buddies_img_05.png' },
    { id: 'b06', name: 'Epilogue: Scaredy Cow',   image: '/img/valorant/buddies_img_06.png' },
    { id: 'b07', name: '5 Years',                 image: '/img/valorant/buddies_img_07.png' },
    { id: 'b08', name: 'Paracord',                image: '/img/valorant/buddies_img_08.png' },
    { id: 'b09', name: 'Apple a Day',             image: '/img/valorant/buddies_img_09.png' },
    { id: 'b10', name: 'Ep 7 // 1 Coin',          image: '/img/valorant/buddies_img_10.png' },
    { id: 'b11', name: 'Echo Frame',              image: '/img/valorant/buddies_img_11.png' },
    { id: 'b12', name: 'Wing It',                 image: '/img/valorant/buddies_img_12.png' },
  ],
};

// Fortnite locker layout pieces — used by shop-details-4.html (fortnite_four).
const FORTNITE_ACCOUNT_LEVEL: ProductLevel = {
  value: 13,
  label: 'Account Level',
  description:
    '— is the sum of all seasonal levels and achievements earned throughout seasons. It maintains account progress, counting all Battle Pass levels or completed Save the World missions, playing LEGO mode, and even completing daily challenges.',
};

const FORTNITE_ACCOUNT_STATS: ProductStat[] = [
  { label: 'Wins', value: '5', icon: 'wins' },
  { label: 'Matches', value: '24', icon: 'matches' },
  { label: 'Gold Bars', value: '0', icon: 'gold_bars' },
  { label: 'Available V-Bucks', value: '0', icon: 'vbucks' },
  { label: 'Gifts Sent', value: '0', icon: 'gifts_sent' },
  { label: 'Gifts Received', value: '0', icon: 'gifts_received' },
  { label: 'Available Return Tickets', value: 'No', icon: 'tickets_available' },
  { label: 'Used Return Tickets', value: 'No', icon: 'tickets_used' },
  { label: 'Skins', value: '1', icon: 'skins' },
  { label: 'Back Blings', value: '2', icon: 'back_blings' },
  { label: 'Pickaxes', value: '1', icon: 'pickaxes' },
  { label: 'Emotes', value: '5', icon: 'emotes' },
  { label: 'Gliders', value: '1', icon: 'gliders' },
  { label: 'Exclusives', value: '1', icon: 'exclusives' },
];

const FORTNITE_LOCKER: ProductLocker = {
  tabs: [
    {
      key: 'all',
      label: 'All',
      groups: [
        {
          title: 'Exclusives',
          count: 4,
          purchased: 0,
          vbucks: 0,
          items: [
            { id: 'ex1', name: 'Blue Squire', image: '/img/fortnite/locker_img_01.jpg' },
            { id: 'ex2', name: 'Royale Knight', image: '/img/fortnite/locker_img_02.jpg' },
            { id: 'ex3', name: 'Floss', image: '/img/fortnite/locker_img_03.jpg' },
            { id: 'ex4', name: 'Take The L', image: '/img/fortnite/locker_img_04.jpg' },
          ],
        },
        {
          title: 'Skins',
          count: 29,
          purchased: 2,
          vbucks: 2000,
          items: [
            { id: 'sk1', name: 'Blue Squire', image: '/img/fortnite/locker_img_01.jpg' },
            { id: 'sk2', name: 'Royale Knight', image: '/img/fortnite/locker_img_02.jpg' },
            { id: 'sk3', name: 'Dark Voyager', image: '/img/fortnite/locker_img_05.jpg' },
            { id: 'sk4', name: 'Drift', image: '/img/fortnite/locker_img_06.jpg' },
            { id: 'sk5', name: 'Peter Griffin', image: '/img/fortnite/locker_img_07.jpg' },
            { id: 'sk6', name: 'Spectra Knight', image: '/img/fortnite/locker_img_08.jpg' },
          ],
        },
      ],
    },
    {
      key: 'exclusives',
      label: 'Exclusive',
      groups: [
        {
          title: 'Exclusives',
          count: 4,
          items: [
            { id: 'exc1', name: 'Blue Squire', image: '/img/fortnite/locker_img_01.jpg' },
            { id: 'exc2', name: 'Royale Knight', image: '/img/fortnite/locker_img_02.jpg' },
            { id: 'exc3', name: 'Floss', image: '/img/fortnite/locker_img_03.jpg' },
            { id: 'exc4', name: 'Take The L', image: '/img/fortnite/locker_img_04.jpg' },
          ],
        },
      ],
    },
    {
      key: 'skins',
      label: 'Skins',
      groups: [
        {
          title: 'Skins',
          count: 29,
          items: [
            { id: 's1', name: 'Blue Squire', image: '/img/fortnite/locker_img_01.jpg' },
            { id: 's2', name: 'Royale Knight', image: '/img/fortnite/locker_img_02.jpg' },
            { id: 's3', name: 'Dark Voyager', image: '/img/fortnite/locker_img_05.jpg' },
            { id: 's4', name: 'Drift', image: '/img/fortnite/locker_img_06.jpg' },
          ],
        },
      ],
    },
    {
      key: 'back_blings',
      label: 'Back Blings',
      groups: [
        {
          title: 'Back Blings',
          count: 2,
          items: [
            { id: 'b1', name: 'Sir Glider the Brave', image: '/img/fortnite/locker_img_03.jpg' },
            { id: 'b2', name: 'Royale Shield', image: '/img/fortnite/locker_img_04.jpg' },
          ],
        },
      ],
    },
    {
      key: 'pickaxes',
      label: 'Pickaxes',
      groups: [
        {
          title: 'Pickaxes',
          count: 1,
          items: [{ id: 'p1', name: 'Default Pickaxe', image: '/img/fortnite/locker_img_07.jpg' }],
        },
      ],
    },
    {
      key: 'gliders',
      label: 'Gliders',
      groups: [
        {
          title: 'Gliders',
          count: 1,
          items: [{ id: 'g1', name: 'OG Glider', image: '/img/fortnite/locker_img_08.jpg' }],
        },
      ],
    },
    {
      key: 'emotes',
      label: 'Emotes',
      groups: [
        {
          title: 'Emotes',
          count: 5,
          items: [
            { id: 'e1', name: 'Floss', image: '/img/fortnite/locker_img_03.jpg' },
            { id: 'e2', name: 'Take The L', image: '/img/fortnite/locker_img_04.jpg' },
            { id: 'e3', name: 'Default Dance', image: '/img/fortnite/locker_img_05.jpg' },
            { id: 'e4', name: 'Orange Justice', image: '/img/fortnite/locker_img_06.jpg' },
            { id: 'e5', name: 'Wave', image: '/img/fortnite/locker_img_07.jpg' },
          ],
        },
      ],
    },
  ],
};

const FORTNITE_SEASONS: ProductSeasons = {
  current: {
    number: 8,
    stats: [
      { label: 'Zero Build Rank', value: 'null (0 %)', icon: 'rank' },
      { label: 'Battle Royale Rank', value: 'Бронза I (0 %)', icon: 'rank' },
      { label: 'Level', value: '1', icon: 'level' },
      { label: 'Season Wins', value: 'No', icon: 'season_wins' },
      { label: 'BP Level', value: '1', icon: 'bp_level' },
      { label: 'BP Purchased', value: 'No', icon: 'bp_purchased' },
      { label: 'Last Match', value: '09.05.2024', icon: 'last_match' },
    ],
  },
  chapter_title: 'Chapter 1',
  history_background: '/img/fortnite/seasons-img.jpg',
  history: [
    { season: 1, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
    { season: 2, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
    { season: 3, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
    { season: 4, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
    { season: 5, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
    { season: 6, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
    { season: 7, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
    { season: 8, level: 1, season_wins: 'No', bp_level: 1, bp_purchased: 'No' },
  ],
};

// Hydrate a product with the rich payload its detail layout expects. Called
// only by `getMockProduct(slug)` — the catalog `getMockProducts()` path
// keeps the lean row. Mirrors how the Laravel ProductDetailResource will
// add the heavy JSON columns to the `/products/{slug}` response.
export function applyDetailTemplate(p: Product): Product {
  // Skip if the product already carries its own rich data (e.g. a hand-crafted
  // mock with custom description_sections). Lets us override per-product later.
  if (
    p.agents_detailed?.length ||
    p.locker ||
    p.account_stats?.length ||
    p.seasons ||
    p.description_sections?.length
  ) {
    return p;
  }

  const merged: Product = { ...p };

  switch (accountTypeToLayout(p.account_type)) {
    case 'rich': // shop-details.html — valorant inactive_exclusive
      merged.agents_detailed = VALORANT_AGENTS;
      merged.agents_count = 28;
      merged.profile_info = VALORANT_PROFILE_INFO;
      merged.skin_inventory = VALORANT_SKIN_INVENTORY;
      merged.skin_filters = VALORANT_SKIN_FILTERS;
      merged.buddy_inventory = VALORANT_BUDDY_INVENTORY;
      merged.description_sections = VALORANT_BUY_DESCRIPTION_SECTIONS;
      break;

    case 'fortnite_four': // shop-details-4.html — fortnite nfa_inactive
      merged.account_level = FORTNITE_ACCOUNT_LEVEL;
      merged.account_stats = FORTNITE_ACCOUNT_STATS;
      merged.locker = FORTNITE_LOCKER;
      merged.seasons = FORTNITE_SEASONS;
      merged.description_sections = VALORANT_BUY_DESCRIPTION_SECTIONS;
      break;

    case 'simple_two':    // shop-details-2.html — valorant verified, fortnite nfa_random/nfa_guaranteed
    case 'simple_three':  // shop-details-3.html — legends standard
      merged.description_sections = FORTNITE_BUY_DESCRIPTION_SECTIONS;
      break;
  }

  if (!merged.guarantee) merged.guarantee = GUARANTEE;
  return merged;
}
