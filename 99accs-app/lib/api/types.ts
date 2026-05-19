export type Game = 'valorant' | 'fortnite' | 'legends';
export type Region = 'na' | 'eu' | 'apac' | 'latam' | 'br';

// Drives the detail-page layout via `accountTypeToLayout()`. Each value
// corresponds to one of the four `shop-details*.html` source layouts.
// Skin differentiation (random vs guaranteed) is now in the Skins taxonomy.
export type AccountType =
  | 'verified'           // valorant VERIFIED section          → simple_two
  | 'inactive_exclusive' // valorant INACTIVE EXCLUSIVE        → rich
  | 'nfa'               // fortnite NFA (skin type via taxonomy) → simple_two
  | 'nfa_inactive'       // fortnite NFA Inactive Accounts     → fortnite_four
  | 'standard';          // legends (all regional sections)    → simple_three

// Names the rendered detail-page body. Returned by `accountTypeToLayout()`.
export type DetailLayout = 'rich' | 'simple_two' | 'simple_three' | 'fortnite_four';

// Groups products into catalog sections (the `<h2>` headers on shop.html /
// shop-2.html / shop-3.html). For Valorant + Fortnite the section follows
// `account_type`; for Legends it follows the server region (EUW, TR, LAS).
// Carried per-product so the Laravel admin can re-section without code edits.
export interface ProductSection {
  slug: string;   // e.g. 'verified' | 'inactive_exclusive' | 'nfa_random' | 'euw' | 'tr' | 'las'
  label: string;  // e.g. 'VERIFIED' | 'Europe West (EUW)'
  order: number;  // 1-based display order within the game's catalog page
}

// CountryCode is the display badge text on a product. The frontend lowercases it
// for the CSS class modifier (`country__code na`, `country__code euw`, etc.).
// Common values: NA, EU, EUW, EUNE, AP, KR, CN, RU, LATAM, LAS, BR, TR. Backend
// may emit any short string here — keep it permissive.
export type CountryCode = string;

export type CategoryIconKey =
  | 'random_skins'
  | 'skins_count'
  | 'champions'
  | 'mail_access'
  | 'exclusive_skins';

export interface ProductCategory {
  id: number;
  label: string;
  icon: CategoryIconKey;
}

export interface ProductCountry {
  // Display text inside the badge (e.g. "NA", "EUW", "LAS", "TR"). Empty
  // string means "no badge" — Fortnite NFA cards have no country chip in HTML.
  code: CountryCode;
  // Small flag image rendered next to the title. Null when there is no flag
  // (most APAC / LATAM / KR / CN / RU / TR cards).
  flag: string | null;
  // Optional CSS class-modifier override. Defaults to `code.toLowerCase()`.
  // Used when text and class diverge — e.g. Legends LAS cards render
  // `<span class="country__code ap">LAS</span>` (text LAS, class ap).
  class_modifier?: string;
}

export interface ColorVariant {
  name: string;
  hex: string;
  img: string;
}

export interface ProductAgentEntry {
  id: string;
  image: string;
  role_icon?: string | null;
}

export interface DescriptionFaqItem {
  question: string;
  answer: string;
}

export type ProductDescriptionSection =
  | { heading: string; type: 'paragraph'; items: string[] }
  | { heading: string; type: 'list'; items: string[]; list_class?: string }
  | { heading: string; type: 'faq'; items: DescriptionFaqItem[] };

export interface ProductStat {
  label: string;
  value: string;
  icon?: ProductStatIcon;
}

export type ProductStatIcon =
  | 'wins'
  | 'matches'
  | 'gold_bars'
  | 'vbucks'
  | 'gifts_sent'
  | 'gifts_received'
  | 'tickets_available'
  | 'tickets_used'
  | 'skins'
  | 'back_blings'
  | 'pickaxes'
  | 'emotes'
  | 'gliders'
  | 'exclusives';

export interface ProductLevel {
  value: number;
  label?: string;
  description?: string;
}

export interface ProductLockerItem {
  id: string;
  name: string;
  image: string;
  rarity?: string;
}

export interface ProductLockerGroup {
  title: string;
  count?: number;
  purchased?: number;
  vbucks?: number;
  items: ProductLockerItem[];
}

export type LockerTabKey =
  | 'all'
  | 'exclusives'
  | 'skins'
  | 'back_blings'
  | 'pickaxes'
  | 'gliders'
  | 'emotes';

export interface ProductLockerTab {
  key: LockerTabKey;
  label: string;
  groups: ProductLockerGroup[];
}

export interface ProductLocker {
  tabs: ProductLockerTab[];
}

export type CurrentSeasonIconKey =
  | 'rank'
  | 'level'
  | 'season_wins'
  | 'bp_level'
  | 'bp_purchased'
  | 'last_match';

export interface ProductSeasonStat {
  label: string;
  value: string;
  icon?: CurrentSeasonIconKey;
}

export interface ProductCurrentSeason {
  number: number;
  stats: ProductSeasonStat[];
}

export interface ProductSeasonHistoryEntry {
  season: number;
  level: number;
  season_wins: string;
  bp_level: number;
  bp_purchased: string;
}

export interface ProductSeasons {
  current?: ProductCurrentSeason;
  history?: ProductSeasonHistoryEntry[];
  chapter_title?: string;
  history_background?: string;
}

export interface ProductGuarantee {
  title: string;
  body: string;
}

// ── Valorant skin + buddies inventory (rich/inactive_exclusive layout) ────
// Mirrors `.shop__details-skin` and `.shop__details-buddies` in
// shop-details.html. The sidebar filter (Rarity + Weapon Type) is driven
// by `skin_filters`; the grid cards by `skin_inventory.items`.

// Rarity tier — drives both the sidebar checkbox + the per-card icon in the
// top-right of `.skin__thumb`. The string keys are the API's enum; UI labels
// + icons come from `skin_filters.rarities`.
export type SkinRarity = 'ultra' | 'exclusive' | 'premium' | 'deluxe' | 'select';

// Weapon-type bucket. Keys are stable API values; labels and counts are
// surfaced by `skin_filters.weapon_types`.
export type WeaponTypeKey =
  | 'melee'
  | 'rifles'
  | 'sniper_rifles'
  | 'sidearms'
  | 'smgs'
  | 'shotguns'
  | 'machine_guns';

export interface SkinColorVariant {
  id: string;
  image: string;
}

export interface ProductSkinItem {
  id: string;
  name: string;
  image: string;
  rarity: SkinRarity;
  weapon_type: WeaponTypeKey;
  // 1..5 — number of dots rendered in `.skin__card-level`. Optional; falsy → omit list.
  levels?: number;
  // When present, renders the `.skin__card-color` row of swatches above the levels.
  color_variants?: SkinColorVariant[];
  // CSS modifier flag for the `.skin__thumb` div. Currently only `'two'` is used,
  // which becomes `'shop__thumb shop__thumb-two'`-style alternate framing on a
  // small subset of cards in the HTML reference.
  thumb_modifier?: 'two' | null;
}

export interface ProductBuddy {
  id: string;
  name: string;
  image: string;
}

// Summary stats rendered as `.inventory-title-wrap` above each grid.
export interface ProductInventorySummary {
  total: number;
  purchased: number;
  vp: number;
}

export interface ProductSkinInventory extends ProductInventorySummary {
  items: ProductSkinItem[];
}

export interface ProductBuddyInventory extends ProductInventorySummary {
  items: ProductBuddy[];
}

export interface SkinRarityOption {
  key: SkinRarity;
  label: string;
  icon: string;        // /img/icons/sidebar_iconNN.svg
}

export interface WeaponTypeOption {
  key: WeaponTypeKey;
  label: string;
  count: number;
}

export interface ProductSkinFilters {
  rarities: SkinRarityOption[];
  weapon_types: WeaponTypeOption[];
}

export interface ProfileStat {
  icon: string;
  value: string;
}

export interface ProfileRank {
  image: string;
  title: string;
  label: string;
}

export type ProfileFeatureIcon = 'mail' | 'clock' | 'phone';

export interface ProfileFeature {
  icon: ProfileFeatureIcon;
  title: string;
  red?: boolean;
}

export interface ProductProfileInfo {
  region?: string;
  region_icon?: string;
  profile_image?: string;
  profile_stats?: ProfileStat[];
  inventory_value?: { label: string; value: string; icon: string };
  ranks?: ProfileRank[];
  features?: ProfileFeature[];
}

export interface Product {
  id: number;
  slug: string;
  game: Game;
  account_type: AccountType;
  section: ProductSection;
  title: string;
  price: number;
  price_max?: number | null;
  old_price?: number | null;
  country: ProductCountry;
  categories: ProductCategory[];
  images: string[];
  // When true, the catalog card renders the `shop__thumb-gallery` popup (the
  // little image-stack icon with a count like `6+`). Independent of
  // `account_type` — some inactive_exclusive cards don't have it, some
  // standard cards (Legends EUW) do. Falsy → no popup.
  has_gallery?: boolean;
  discount_percent?: number | null;
  badge_icon?: string | null;
  regions?: Region[];    // M:N — denormalized from product_region pivot
  rank?: string | null;
  agents?: string[];
  skins?: string[];
  buddies?: string[];
  description: string;
  short_description?: string | null;
  specs: Record<string, string>;
  stock: number;
  created_at: string;
  related?: Product[];

  agents_detailed?: ProductAgentEntry[];
  agents_count?: number;
  profile_info?: ProductProfileInfo;
  skin_inventory?: ProductSkinInventory;
  buddy_inventory?: ProductBuddyInventory;
  skin_filters?: ProductSkinFilters;
  account_level?: ProductLevel;
  account_stats?: ProductStat[];
  locker?: ProductLocker;
  seasons?: ProductSeasons;
  description_sections?: ProductDescriptionSection[];
  min_quantity?: number;
  last_match_label?: string;
  guarantee?: ProductGuarantee;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PaginationLinks {
  first: string | null;
  last: string | null;
  next: string | null;
  prev: string | null;
}

export interface ApiCollection<T> {
  data: T[];
  meta: PaginationMeta;
  links: PaginationLinks;
}

export interface ApiResource<T> {
  data: T;
}

export interface ApiError {
  message: string;
  errors?: Record<string, string[]>;
}

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  created_at: string;
}

export interface AuthLoginResponse {
  data: {
    token: string;
    user: AuthUser;
  };
}

export interface Order {
  id: number;
  total: number;
  status: 'pending' | 'processing' | 'completed' | 'cancelled';
  created_at: string;
  items: OrderItem[];
}

export interface OrderItem {
  id: number;
  product_id: number;
  product_title: string;
  product_image: string;
  quantity: number;
  price_at_purchase: number;
}

export interface WishlistItem {
  id: number;
  product: Product;
  created_at: string;
}

export interface SupportArticle {
  id: number;
  slug: string;
  title: string;
  content: string;
  category: string;
  created_at: string;
}

// ── Support tickets ───────────────────────────────────────────────────────
// Auth-gated. A logged-in user opens a ticket against a Product/Game,
// staff/system replies, the conversation lives in `messages[]`. Each ticket
// belongs to exactly one user (the API never returns other users' tickets).

export type SupportTicketStatus = 'new' | 'open' | 'closed';

export interface SupportTicketMessage {
  id: number;
  ticket_id: number;
  // `true` when this message was written by the ticket's owner. Rendered as
  // "You" in the comment thread; otherwise the staff member's name renders.
  is_owner: boolean;
  author_name: string;
  author_avatar: string;
  body: string;
  // `true` for the very first message (the original ticket body). The UI
  // labels owner-authored opening messages "started the conversation"
  // instead of "replied".
  is_opening?: boolean;
  created_at: string;
}

export interface SupportTicket {
  id: number;
  // Human-readable id displayed on the table & thread header. Distinct from
  // the numeric primary key. Format: `#NNNNN`.
  ticket_number: string;
  user_id: number;
  // Product context — drives the logo column on the table.
  game: Game;
  // Optional reference to the order this ticket is about. Format: `#AAAA12`.
  order_number?: string | null;
  subject: string;
  // First-message excerpt used in the table's "Conversation" column.
  preview: string;
  status: SupportTicketStatus;
  reply_count: number;
  created_at: string;
  last_reply_at?: string | null;
  // Only present on detail reads (`GET /support/tickets/:id`).
  messages?: SupportTicketMessage[];
}

export interface AccountDashboard {
  order_count: number;
  wishlist_count: number;
  total_spent: number;
  recent_orders: Order[];
}

export type BannerIcon = 'shield_check' | 'trophy' | 'delivery' | 'warranty';

export interface BannerSubtitle {
  icon: BannerIcon;
  text: string;
}

export interface BannerHeading {
  prefix: string;
  highlight: string;
  suffix: string;
}

export interface BannerFeature {
  id: number;
  icon: BannerIcon;
  text: string;
}

export interface BannerCategory {
  id: number;
  href: string;
  image: string;
  alt: string;
}

export interface HomeBanner {
  background_image: string;
  subtitle: BannerSubtitle;
  heading: BannerHeading;
  description: string;
  features: BannerFeature[];
  categories: BannerCategory[];
}

export interface HomeAbout {
  background_image: string;
  image: string;
  title: string;
  paragraphs: string[];
  stats: {
    happy_customers: number;
    accounts_sold: number;
  };
}

export interface WorkStep {
  id: number;
  num: string;
  title: string;
  text: string;
}

export interface HomeWork {
  background_image: string;
  title: string;
  steps: WorkStep[];
  images: string[];
}

export interface FeatureItem {
  id: number;
  title: string;
  icon: string;
  text: string;
}

export interface FeaturesHeading {
  prefix: string;
  user_count: number;
  suffix: string;
}

export interface HomeFeatures {
  background_image: string;
  heading: FeaturesHeading;
  items: FeatureItem[];
}

export interface Testimonial {
  id: number;
  title: string;
  text: string;
  author: string;
  rating: number;
}

export interface HomeTestimonials {
  background_image: string;
  title: string;
  items: Testimonial[];
}

export type CtaPlatform = 'telegram' | 'discord';

export interface CtaButton {
  id: number;
  platform: CtaPlatform;
  label: string;
  url: string;
}

export interface HomeCta {
  background_image: string;
  title_lines: string[];
  buttons: CtaButton[];
}

export interface HomeData {
  banner: HomeBanner;
  about: HomeAbout;
  work: HomeWork;
  features: HomeFeatures;
  testimonials: HomeTestimonials;
  cta: HomeCta;
}

export interface CartItem {
  id: number;
  product_id: number;
  product: Product;
  quantity: number;
}
