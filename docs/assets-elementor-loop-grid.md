# Elementor Loop Grid — RW Dealer Portal Assets

Two custom query IDs are registered by the plugin so Elementor's Loop Grid
widget can query `rw_asset` posts without needing a CPT archive or any
modification to the existing `/dealer-assets/` page.

---

## Query IDs

| Query ID | What it returns |
|---|---|
| `rwdp_top_level_assets` | All top-level `rw_asset` posts (`post_parent = 0`), ordered by Menu Order |
| `rwdp_child_assets` | Child `rw_asset` posts of the current single post, ordered by Menu Order |

---

## Part 1 — Dealer Assets page (top-level grid)

### Step 1 — Create a Loop Item template

1. In wp-admin go to **Templates → Theme Builder → Loop Item → Add New**.
2. Choose **Loop Item** and name it something like `Asset Card`.
3. The dynamic context will be `rw_asset` — search for any existing asset and
   use it as the preview.
4. Build the card however you like. Suggested elements:
   - **Featured Image** widget (link to post URL)
   - **Post Title** widget (tag H3, link to post URL)
   - **Post Excerpt** widget (optional)
   - A **Button** widget with text "View Assets →" linked to the post URL
5. **Publish** the template.

### Step 2 — Add a Loop Grid to the dealer-assets page

1. Edit the `/dealer-assets/` page with Elementor.
2. Add a **Loop Grid** widget.
3. In the **Query** tab:
   - **Source** → `Custom Query`
   - **Query ID** → `rwdp_top_level_assets`
4. In the **Layout** tab choose your column count and gap.
5. Under **Loop Item** select the `Asset Card` template you created in Step 1.
6. **Update** the page.

> **Note:** The existing `[rwdp_assets]` shortcode can remain on the page as a
> fallback. If you want a pure Elementor build, remove the shortcode block.

---

## Part 2 — Single Asset page (child grid drill-down)

When a top-level asset is clicked, it opens `single-rw_asset.php`. This
template already renders child assets as cards via PHP. If you want to replace
that with an Elementor Loop Grid, follow the steps below.

### Step 1 — Create a Single Asset Elementor template

1. Go to **Templates → Theme Builder → Single → Add New**.
2. Choose **Single Post**, select `rw_asset` as the post type, name it
   `Single Asset`.
3. Build the layout:
   - **Post Title** widget (H1)
   - **Post Content** widget or a **Text Editor** widget with the
     `_rwdp_asset_description` dynamic tag (via ACF/custom field if mapped)
   - A **Loop Grid** widget for the children (see below)
   - Optionally the gallery/video/download sections via shortcode or custom
     widgets
4. In the **Loop Grid** widget's **Query** tab:
   - **Source** → `Custom Query`
   - **Query ID** → `rwdp_child_assets`
5. Set display conditions: **Single → rw_asset**.
6. **Publish** the template.

> **Note:** Once this theme builder template is active, `single-rw_asset.php`
> is bypassed by Elementor for pages edited this way. Keep the PHP template in
> place as a non-Elementor fallback.

---

## Access Gating

The PHP access gate in `single-rw_asset.php` (and `access-control.php`)
redirects unauthenticated users to the login page. Elementor theme builder
templates load *after* WordPress routing, so the PHP gate still fires — you do
**not** need to add any Elementor conditions for access control.

---

## Menu Order

Asset cards are sorted by **Menu Order** (the "Order" field in the Page
Attributes box in wp-admin). Set these on each `rw_asset` post to control the
display order in the grid.
