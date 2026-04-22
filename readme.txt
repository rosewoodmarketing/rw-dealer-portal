=== RW Dealer Portal ===
Contributors: rosewoodmarketing
Tags: dealer, dealer finder, dealer portal, dealer locator, elementor
Requires at least: 6.5
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.0.10
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A private dealer portal and public-facing dealer finder for businesses that operate with a dealer network.

== Description ==

RW Dealer Portal gives businesses with dealer networks two distinct tools in one plugin:

**Dealer Portal (private)**
Dealers log in to access a password-protected portal where they can download digital assets, manage their public-facing profile, submit forms, and view account details. Role-based access control keeps portal pages invisible to non-dealers.

**Dealer Finder (public)**
Visitors search by ZIP code or city to find nearby dealers. Results are filtered by radius and optionally by dealer type. The finder is powered by the Google Maps Geocoding API and rendered as a fully-styled Elementor widget.

**Key features:**

* Custom `rw_dealer` post type with geocoded address fields
* `rw_dealer_type` taxonomy for categorising dealers
* Dedicated `Dealer` WordPress user role
* Protected uploads directory — assets served only to authenticated dealers
* Auto-generated portal pages on activation (Login, Dashboard, Assets, Account, Dealer Profile)
* Fluent Forms integration for dealer contact / lead forms
* Elementor widget — **Dealer Search Bar** — with full style controls (typography, colours, border, padding, max-width, and more)
* GitHub-based auto-updater — updates appear in the standard WordPress Updates screen
* WooCommerce compatibility — finder assets are suppressed on product category archive pages

== Installation ==

1. Upload the `rw-dealer-portal` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. Go to **RW Dealer Portal → Settings** and enter your Google Maps API key.
4. Assign the **Dealer** role to any user who should have portal access.
5. Add the **Dealer Search Bar** Elementor widget to any page to display the public finder.

== Frequently Asked Questions ==

= Does this plugin require Elementor? =
The Dealer Finder widget requires Elementor (free or Pro). The portal shortcodes and admin features work without it.

= What Google Maps API services are needed? =
The Geocoding API (for geocoding dealer addresses on save) and the Maps JavaScript API (for the map view in the finder) must both be enabled on your API key.

= How does the auto-updater work? =
The plugin checks for new releases on its GitHub repository. When a new release is published with a version tag (e.g. `v1.0.2`), it appears as an available update in **Dashboard → Updates**.

= How do I restrict the finder to a specific dealer type? =
Use the **Lock to Dealer Type** control on the Dealer Search Bar widget and enter the dealer-type slug. The finder will only return dealers of that type.

== Screenshots ==

1. Public-facing Dealer Finder with ZIP/city search and radius filter.
2. Dealer Search Bar Elementor widget style controls.
3. Protected dealer portal dashboard.
4. Plugin settings screen (Google Maps API key, page assignments).

== Changelog ==

= 1.0.10 =
* Fixed: "Lock to Dealer Type" on the Dealer Search Bar widget now correctly filters by `rw_dealer_type` taxonomy slug when no matching ACF relationship field is found. Previously, if the ACF fields were not configured or the slug didn't match an ACF relationship option, the lock was silently ignored and all dealers were returned.

= 1.0.9 =
* Added: Seven Elementor Dynamic Tags for dealer fields — Address, Phone, Website, Email, Hours, Logo, and Directions Link — usable in any Elementor template on `rw_dealer` posts.
* Added: Website field normalises on save — if no URL scheme is present, `https://` is prepended automatically before storage.
* Added: `show_website` toggle to the Dealer Results List widget with dedicated typography, colour, and padding style controls.
* Added: Website field displayed in map popup info windows and result cards (scheme stripped for clean display).
* Added: Google Maps API setup instructions added to the Maps API Keys settings tab as a collapsible details block.
* Added: Portal Overview admin dashboard redesigned — four metric cards (Dealers, Assets, Form Submissions, Pending Registrations) with counts, descriptions, and action buttons.
* Changed: Filter dropdowns no longer require the "Enable Filter Dropdowns" master toggle — dropdowns appear automatically when ACF fields are checked in Dealer Project Relation settings.
* Fixed: Info window logo `src` attribute was not attribute-escaped (XSS).
* Fixed: Info window directions text was not HTML-escaped before DOM insertion.
* Fixed: `RWDP_Tag_Website` dynamic tag removed `URL_CATEGORY` — `render()` outputs the scheme-stripped display string which would have produced a broken relative URL in Elementor URL fields.
* Fixed: `RWDP_Tag_Logo` now returns an empty array instead of `['url' => false]` when the attachment has been deleted from the media library.

= 1.0.8 =
* Added: Image Resolution controls for thumbnail and logo in the Dealer Results List widget Content tab.
* Improved: Image size options are now dynamically populated from all registered WordPress image sizes (core + custom).
* Changed: AJAX handler now accepts and validates `thumbnail_image_size` and `logo_image_size` parameters, replacing the hardcoded `'thumbnail'` size for both `feat_img` and `logo_url`.

= 1.0.7 =
* Added: Settings page redesigned with left-side tabbed navigation (Maps API Keys, Contact Form, Portal Pages, Restricted Pages).
* Added: Dealer Types taxonomy now manageable via dedicated admin submenu — no longer requires editing a dealer to manage types.
* Added: Removed redundant "Add New Dealer" and "Add New Asset" sidebar entries; use the "Add New" button on each list screen instead.
* Fixed: Deprecated `preg_replace()` notice on the Submissions page when `paginate_links()` returns null (single page of results).
* Changed: Removed unused Asset Category taxonomy and all references to it.
* Changed: Elementor stub widgets (Dashboard, My Account, My Requests) hidden from the widget panel until fully built out.
* Changed: `wp_localize_script` for Dealer Finder nonce correctly scoped — no longer runs on every frontend page load.
* Changed: Plugin now requires WordPress 6.5+ and PHP 8.1+.

= 1.0.6 =
* Changed: Contact form submissions are no longer stored as a custom post type. The Fluent Forms entry is tagged with the dealer ID in FF's own submission_meta table instead.
* Changed: Admin Contact Submissions page and portal [rwdp_my_requests] shortcode now query FF's tables directly — all form fields are displayed regardless of field naming.
* Added: "View Entry" button on each admin table row links directly to the full Fluent Forms entry detail page.
* Added: CSV export now includes Entry ID and Entry URL columns.
* Added: "Not configured" notice on admin submissions page when no contact form is set.

= 1.0.5 =
* Fixed: Icon Size control on the Search Bar widget now correctly targets rendered `<i>` and `<svg>` elements.
* Added: Get Directions button moved to Content tab on Dealer Results List widget; icon position (before/after) control added.
* Added: Editable "Get Directions" button text on both the Dealer Map and Dealer Results List widgets.
* Added: Editable "View on Map" button text on the Dealer Results List widget.
* Improved: Contact button text labels now clarify context — "Contact Button Text (card)" on the List widget and "Contact Button Text (popup)" on the Map widget.

= 1.0.4 =
* Added: Icon and icon position controls to the Get Directions button on the Dealer Map widget popup.
* Added: Icon Size control to the Get Directions button on both the Dealer Map and Dealer Results List widgets.
* Fixed: Icon size selectors now correctly target rendered `<i>` and `<svg>` elements.

= 1.0.3 =
* Fixed: administrators missing custom CPT capabilities on sites where the plugin activation hook never fired (cloned DBs, file-only deploys). Capabilities now self-heal on every load via `plugins_loaded`.

= 1.0.2 =
* Added responsive Max Width control to the Dealer Search Bar search input.

= 1.0.1 =
* Initial public release.

= 1.0.0 =
* Internal beta release.

== Upgrade Notice ==

= 1.0.2 =
Minor style control addition — no database changes required.
