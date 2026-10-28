# Design Blueprint & UI Specifications: Alokito Gour Clone

## 1. Visual Identity & Design Tokens

Extracted through Reverse-Engineering (REA) from `https://alokitogour.com/`:

### 1.1 Color Palette
| Token Name | Hex Code | Purpose / Usage |
| :--- | :--- | :--- |
| `--brand-primary` | `#f3730d` | Primary brand orange. Top bar highlights, navbar dividers, button accents, category tags. |
| `--brand-deepred` | `#c82333` | Breaking news ticker label, urgent badges, danger alerts. |
| `--brand-green` | `#0abb75` | Subtle category badge backgrounds, success alerts, online status. |
| `--text-dark` | `#222222` | Primary article headlines, heavy titles, navigation text. |
| `--text-body` | `#4b5563` | Body copy, article paragraphs, summaries, meta descriptions. |
| `--text-muted` | `#6c757d` | Reporter bylines, timestamps, view counts, breadcrumbs. |
| `--bg-light` | `#f8f9fa` | Page background, widget cards, ticker track, subtle sections. |
| `--bg-white` | `#ffffff` | Article card containers, modals, navbar background. |
| `--border-color` | `#e5e7eb` | Content dividers, card borders, table separators. |

---

### 1.2 Typography & Font Stack
- **Primary Bengali Font**: `SolaimanLipi`, sans-serif (imported via `https://fonts.maateen.me/solaiman-lipi/font.css`).
- **Fallback Font Stack**: `Kalpurush`, 'Noto Sans Bengali', Arial, sans-serif.
- **Hierarchy Scale**:
  - Main Lead Headline: `28px` to `34px` / `700 Bold` / Line-height: `1.35`
  - Sub-Lead Headline: `20px` to `22px` / `600 Semi-Bold` / Line-height: `1.4`
  - Category Section Heading: `22px` / `700 Bold` with left orange accent border (`border-left: 4px solid #f3730d`)
  - Grid Card Title: `16px` to `18px` / `600 Semi-Bold` / Line-height: `1.45`
  - Body Text: `17px` / `400 Regular` / Line-height: `1.75`
  - Meta & Bylines: `13px` to `14px` / `400 Regular` / Line-height: `1.2`

---

## 2. Responsive Breakpoints

| Device Viewport | Range | Layout Directives |
| :--- | :--- | :--- |
| **Mobile** | `< 640px` | 1-column stacked flow. Sticky header with compact logo (height: 44px). Drawer menu via hamburger toggle. Ticker condensed. Images scale 100% width. Sidebar moves below category blocks. |
| **Tablet** | `640px - 1024px` | 2-column grid. Header displays logo and date. Navigation items wrap or truncate into "আরও" dropdown. Sidebar occupies 33% or stacks. |
| **Desktop** | `> 1024px` | Max container width `1240px` centered. Full topbar with date and social icons. 3-column layout (Main content 8 cols, Sidebar 4 cols). Sticky navigation bar with orange separators. |

---

## 3. Component Wireframes & Blueprints

### 3.1 Header & Navigation Blueprint
```
+-----------------------------------------------------------------------------------+
| Top Bar: [বুধবার, ২৮ অক্টোবর ২০২৬]                       [FB] [YT] [ই-পেপার] [ভিডিও] |
+-----------------------------------------------------------------------------------+
| Logo: [ আলোকিত গৌড় ]                       Ad Slot: [ Header Leaderboard 728x90 ] |
+-----------------------------------------------------------------------------------+
| [প্রচ্ছদ] | [চাঁপাইনবাবগঞ্জ] | [জাতীয়] | [সারাদেশ v] | [রাজনীতি] | [খেলাধুলা] | [অনুসন্ধান 🔍] |
+-----------------------------------------------------------------------------------+
| [সংবাদ শিরোনাম] >>> ডেঙ্গু প্রতিরোধে চাঁপাইনবাবগঞ্জে অসহায় পরিবারের মাঝে মশারি বিতরণ... |
+-----------------------------------------------------------------------------------+
```

### 3.2 Lead News Hero Grid Blueprint
```
+----------------------------------------------------+------------------------------+
| MAIN LEAD STORY (Col 7 / 12)                        | SUB-LEAD STORIES (Col 5 / 12)|
| +------------------------------------------------+ | +--------------------------+ |
| | [ Featured High-Res Thumbnail (16:9) ]         | | | [ Thumb ]  Sub-lead 1     | |
| |                                                | | |            Title & Time  | |
| | Title: চাঁপাইনবাবগঞ্জে ডেঙ্গু প্রতিরোধে...         | | +--------------------------+ |
| | Excerpt: ডেঙ্গু জ্বরের প্রাদুর্ভাব রোধ এবং অসহায়... | | | [ Thumb ]  Sub-lead 2     | |
| | Meta: [চাঁপাইনবাবগঞ্জ] • ৫ মিনিট আগে              | | |            Title & Time  | |
| +------------------------------------------------+ | +--------------------------+ |
+----------------------------------------------------+------------------------------+
```

### 3.3 Main Content & Sidebar Blueprint
```
+----------------------------------------------------+------------------------------+
| CATEGORY BLOCKS (Col 8 / 12)                       | SIDEBAR (Col 4 / 12)         |
|                                                    |                              |
| [ চাঁপাইনবাবগঞ্জ ]                                 | +--------------------------+ |
| +---------------------+  +-----------------------+ | | [ সর্বশেষ ] | [ জনপ্রিয় ]  | |
| | [Lead Thumb]        |  | • ককটেল ও পাউডার উদ্ধার| | +--------------------------+ |
| | সদর উপজেলায় আমবাগান..|  | • কিট প্রদান হাসপাতাল | | | 1. প্রথম খবর শিরোনাম...    | |
| | ৫ ঘন্টা আগে         |  | • ডেঙ্গুতে মৃত্যু...   | | | 2. দ্বিতীয় খবর শিরোনাম...   | |
| +---------------------+  +-----------------------+ | | 3. তৃতীয় খবর শিরোনাম...    | |
|                                                    | +--------------------------+ |
| [ Advertisement Banner: after_lead (728x90) ]      |                              |
|                                                    | [ Ad Slot: sidebar (300x250) ]
| [ জাতীয় ও আন্তর্জাতিক ]                             |                              |
| +---------------------+  +-----------------------+ | +--------------------------+ |
| | [Thumb] বায়ুদূষণ... |  | [Thumb] দিল্লির অ্যালার্ট| | | [ আমার এলাকার খবর ]      | |
| +---------------------+  +-----------------------+ | | বিভাগ ও জেলা নির্বাচন ড্রপডাউন| |
+----------------------------------------------------+------------------------------+
```

### 3.4 Footer Blueprint
```
+-----------------------------------------------------------------------------------+
| [ Logo ]              | প্রধান সম্পাদক ও প্রকাশক: মোহাঃ আব্দুল্লাহ                   |
| সত্যের সাথে, আগামীর পথে | অফিস: ক্লাব সুপার মার্কেট (৪র্থ তলা), সদর, চাঁপাইনবাবগঞ্জ   |
|                       | ফোন: 01333479192 (সম্পাদক), 01333479193 (অফিস)            |
+-----------------------------------------------------------------------------------+
| [প্রচ্ছদ] [চাঁপাইনবাবগঞ্জ] [জাতীয়] [সারাদেশ] [রাজনীতি] [আন্তর্জাতিক] [যোগাযোগ]   |
+-----------------------------------------------------------------------------------+
| কপিরাইট © ২০২৬ আলোকিত গৌড় | সর্বস্বত্ব সংরক্ষিত | কারিগরি সহায়তায়: DataEnvelope    |
+-----------------------------------------------------------------------------------+
```

---

## 4. UI Component Design Specifications

1. **Card Elements**:
   - Card border: `1px solid #e5e7eb` with subtle hover box-shadow (`box-shadow: 0 4px 12px rgba(0,0,0,0.06)`).
   - Image aspect ratios: Standard `16:9` for leads; `4:3` or `1:1` square for sub-lead list thumbnails.
   - Image hover effect: Smooth scale transition (`transform: scale(1.04); transition: transform 0.3s ease`).

2. **Buttons & Badges**:
   - Primary Action: Background `#f3730d`, Text `#ffffff`, Border-radius: `4px`.
   - Category Badges: Background `#f3730d` or `rgba(243, 115, 13, 0.1)`, Text `#f3730d`, Padding: `2px 8px`, Font-size: `12px`.
   - Breaking Ticker Badge: Background `#c82333`, Text `#ffffff`, Font-weight: `700`.

3. **Admin Panel UI Design**:
   - Clean, high-contrast dashboard following modern ERP design standards.
   - Left vertical sidebar in deep slate (`#1e293b`), top navbar in clean white (`#ffffff`).
   - Standardized data tables with zebra striping, status badges (`published` = green, `draft` = amber), and instant action icons.
