=== RW Dealer Portal ===
Contributors: rosewoodmarketing
Tags: dealer, dealer finder, dealer portal, dealer locator, elementor
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.6
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
