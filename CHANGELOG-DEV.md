# RW Dealer Portal Dev Change Notes

---

## Development Conventions

### Elementor Widgets Must Be Thin Wrappers Around Shortcodes

When building a new Elementor widget for this plugin, **do not duplicate logic**. Follow this pattern:

1. Implement all rendering, querying, and output logic in a shortcode function (in `includes/`).
2. The Elementor widget's `render()` method should call that shortcode function or its underlying helpers directly — it should add no business logic of its own.
3. Widget controls map to shortcode attributes. If the shortcode doesn't yet support an attribute, add it to the shortcode first, then expose it as a widget control.

**Why:** Shortcodes remain usable in non-Elementor contexts (block editor, page builders, direct PHP calls). If logic lives only in the widget, it becomes inaccessible outside Elementor and leads to duplication when features need to be shared.

**Example pattern:**
```php
protected function render() {
    $s = $this->get_settings_for_display();
    echo rwdp_my_feature_shortcode([
        'some_attr' => $s['some_control'],
    ]);
}
```

The current Dealer Finder (`rwdp_dealer_finder` shortcode + `RWDP_Dealer_Search_Widget`) follows this convention — `dealer-search-widget.php` is essentially a styled wrapper that passes widget settings into `rwdp_render_filter_dropdowns()` and shared helpers.

---

Date: 2026-05-08
Scope: Portal Manager core-content permissions controls (implemented settings-driven add/edit/delete for Posts, Pages, Media).

## Implemented change summary
1. Added a new Dealer Portal Settings tab (`Portal Manager Access`) to control Portal Manager access to WordPress core content.
2. Added two checkboxes:
- Allow add/edit for Posts, Pages, Media.
- Allow delete for Posts, Pages, Media.
3. Kept sensitive capabilities blocked at all times (plugins/themes/options/tools/theme settings).
4. Updated capability enforcement and menu/admin-bar visibility in access control to read these settings at runtime.

## Why this approach
1. Uses existing plugin architecture (`rwdp_settings` + capability filters) without introducing new roles.
2. Allows instant operational toggles without code deploys after feature lands.
3. Keeps rollback simple: remove tab/settings keys and revert runtime gating logic.

## Files changed
1. `includes/admin-settings.php`
- Added tab slug/label: `portal_manager_access` / `Portal Manager Access`.
- Added checkbox UI.
- Added sanitization keys:
  - `portal_manager_allow_core_content_manage`
  - `portal_manager_allow_core_content_delete`
- Added consistency rule: delete is forced off when manage is off.
- Added hidden field preservation across tab saves.
2. `includes/access-control.php`
- Added helper: `rwdp_get_portal_manager_core_content_permissions()`.
- Updated `rwdp_restrict_portal_manager_caps()` to grant/block core post/page/media caps from settings.
- Updated menu/admin-bar behavior to match manage toggle.
3. `includes/roles.php`
- No changes required; role defaults remain unchanged and runtime filtering handles this feature.

## Rollback plan
1. Revert settings tab additions in `includes/admin-settings.php`.
2. Remove settings key sanitization and hidden-value preservation for the two new options in `includes/admin-settings.php`.
3. Revert capability/menu/admin-bar conditional logic in `includes/access-control.php` to the previous hard-block behavior.
4. Keep role definitions in `includes/roles.php` unchanged (no data migration required).
5. Optional DB cleanup: remove `portal_manager_allow_core_content_manage` and `portal_manager_allow_core_content_delete` from `rwdp_settings`.

## Validation checklist
1. Both checkboxes off: Portal Manager cannot create/edit/delete posts, pages, or media.
2. Manage on, delete off: Portal Manager can create/edit/upload but cannot delete.
3. Both on: Portal Manager can create/edit/delete/upload.
4. Portal Manager still cannot access Plugins, Themes, Settings, or Tools.

---

Date: 2026-04-14
Scope: Dealer Finder type dropdown optionality + ACF relationship field source, implemented with minimal coupling to existing plugin core.

## Why this file exists
This file is for the next developer (human or AI) to quickly understand:
1. What changed.
2. Why it changed.
3. How to revert safely.
4. What edge cases and safeguards were intentionally added.

## Summary of implemented behavior
1. The Dealer Finder type dropdown is optional via settings checkbox.
2. Dropdown options now come from an ACF Relationship/Post Object field on rw_dealer posts.
4. Filter Source Mode now uses ACF relationship field mode.
5. Existing frontend AJAX contract was preserved (type_id and locked_type request keys, type_ids response key).
6. Dealer Finder settings show a warning when relationship mode is misconfigured.
7. Dealer Finder settings show an info notice when relationship mode is configured successfully.
8. Dealer Finder settings now include a diagnostic line showing detected filter option count from the configured relationship field.
9. Relationship option detection now scans all published dealers (not only geocoded dealers) so diagnostics reflect available field data earlier.
10. Relationship value parsing now supports multiple storage/return formats (objects, IDs, serialized arrays, formatted/unformatted ACF reads).
11. Frontend single dealer views now explicitly ensure the top admin bar shows `Edit Dealer` (for users who can edit the dealer post).
12. Dealer Finder map now supports dual filters: ACF relationship dropdown and rw_dealer_type taxonomy dropdown together.

## Files changed

### 1) includes/admin-settings.php
Changes made:
1. Settings sanitization now enforces relationship mode:
- enable_type_dropdown
- filter_source_mode (acf_relationship_field)
- filter_acf_field_name
2. Dealer Finder tab UI now includes:
- Enable Type Dropdown checkbox
- Filter Source Mode (ACF relationship field)
- ACF Relationship Field Name input
3. Dealer Filter Taxonomy selector was removed.
4. Added warning/info notices on the Dealer Finder tab:
- Warning when field name is empty.
- Warning when the field is not a Relationship/Post Object field.
- Info when field setup is valid.
5. Added diagnostic readout in the info notice:
- Shows number of filter options detected from configured relationship field.
- Helps quickly confirm whether dealer data is actually available for dropdown options.
6. Hidden input preservation updated for the revised Dealer Finder settings fields.

Revert options:
1. Full revert of Dealer Finder settings additions:
- Remove sanitize keys for enable_type_dropdown/filter_source_mode/filter_acf_field_name.
- Remove dealer_finder tab from valid_tabs/tabs.
- Remove hidden input block for dealer_finder fields.
- Remove dealer_finder tab form rows and admin notices.
2. Partial revert (keep tab but disable feature):
- Keep tab but set enable_type_dropdown default to 0 and ignore Dealer Finder settings in runtime logic.

### 2) includes/dealer-finder.php
Changes made:
1. Added relationship-field helper functions:
- rwdp_get_dealer_filter_settings()
- rwdp_normalize_related_post_ids( $value )
- rwdp_get_relationship_filter_options( $field_name )
- rwdp_resolve_locked_relationship_id( $locked_value, $options )
2. Updated shortcode behavior:
- Dropdown renders only when:
  - no locked type in shortcode
  - enable_type_dropdown is enabled
  - relationship options are available from configured ACF field
3. Updated AJAX behavior:
- Locked type resolves to related post ID (numeric, slug, or title match).
- Dropdown filter applies only when field name is configured.
- Dealer filtering uses meta_query LIKE against serialized relationship IDs.
- Dealer payload type_ids now comes from relationship/post-object field values.
4. Added safeguards so empty/missing field name does not create invalid meta queries.
5. Option discovery now reads from all published dealers to prevent false zero-count diagnostics before geocoding is complete.
6. Relationship field reading now tries unformatted ACF, formatted ACF, then raw post meta for broader compatibility.
7. Reintroduced taxonomy dropdown (`rw_dealer_type`) alongside relationship dropdown on the map controls.
8. AJAX now accepts both `type_id` (relationship) and `tax_type_id` (taxonomy) and applies both constraints together.
9. Dealer payload now includes `taxonomy_type_ids` in addition to `type_ids` for debugging/extension use.

Revert options:
1. Full revert to previous taxonomy model:
- Remove relationship helper functions.
- Restore taxonomy term retrieval/query logic.
- Restore taxonomy-based type_ids payload logic.
2. Keep relationship helpers but disable feature:
- Force rwdp_get_dealer_filter_settings() to return enabled=false.

### 3) includes/access-control.php
Changes made:
1. Added a dedicated frontend admin-bar hook:
- `add_action( 'admin_bar_menu', 'rwdp_ensure_edit_dealer_admin_bar_node', 1000 )`
2. Added helper function `rwdp_ensure_edit_dealer_admin_bar_node( $wp_admin_bar )` that:
- Runs only on `is_singular( 'rw_dealer' )`
- Checks `current_user_can( 'edit_post', $post_id )`
- Pulls edit URL via `get_edit_post_link( $post_id )`
- Adds/overrides admin-bar node id `edit` with title `Edit Dealer`
3. This preserves dealer-role behavior (admin bar still hidden for `rwdp_dealer`) and only affects users who already see the admin bar.

Why this was needed:
1. On the plugin's frontend dealer template, the default WordPress `edit` node can be missing or not clearly labeled due to theme/plugin admin-bar customizations.
2. Explicitly adding node id `edit` at late priority guarantees visibility and a clear label for editors/managers/admins.

Revert options:
1. Remove `add_action( 'admin_bar_menu', 'rwdp_ensure_edit_dealer_admin_bar_node', 1000 )`.
2. Remove `rwdp_ensure_edit_dealer_admin_bar_node()` function block.
3. No database migration is required for revert.

## Backward compatibility notes
1. JS API contract intentionally unchanged.
2. Existing shortcode attribute dealer_type is still accepted.
3. Existing option rwdp_settings remains same container array.
4. Legacy settings values are tolerated but runtime now enforces relationship mode.

## Relationship mode notes
1. Relationship mode is designed for ACF Relationship/Post Object fields stored on rw_dealer.
2. If ACF is not active, runtime still attempts to read raw post meta values.
3. Runtime now tolerates object/ID/serialized relationship values from ACF/meta storage variations.
4. If field setup is invalid, settings UI shows warning and dropdown will not populate.
5. When valid, settings UI shows info notice confirming configured field name and diagnostic option count.

## QA checklist for next developer
1. Settings page:
- Dealer Finder tab appears.
- Saving any tab does not erase other tab values.
2. Dropdown disabled:
- No type dropdown on map page.
- Finder still loads and returns dealers.
3. Dropdown enabled + valid relationship field:
- Dropdown options reflect unique related posts from dealers.
- Selecting an option narrows results correctly.
4. Locked shortcode type:
- [rwdp_dealer_finder dealer_type="123"] filters by related post ID.
- Slug/title lock values resolve when present in options.
5. Misconfigured field:
- Warning appears in settings.
- No fatal errors on frontend.
6. No PHP warnings/fatal errors on frontend or settings page.
7. With dual filters shown, selecting either dropdown updates map/results immediately.
8. When both dropdowns are selected, results match dealers satisfying both filters.
9. Taxonomy dropdown options come from `rw_dealer_type` terms and can be used independently or with relationship filter.
10. On frontend single dealer pages, eligible users (admins/editors/portal managers with dealer edit caps) see top-bar `Edit Dealer` and link opens wp-admin edit screen for that dealer.
11. Dealer users (`rwdp_dealer`) still do not see admin bar.

## Notes for AI agents
1. Preserve request/response keys used by assets/js/dealer-map.js unless intentionally versioning frontend behavior.
2. Keep settings additive inside rwdp_settings.
3. Avoid introducing hard ACF dependency in bootstrap/activation flow.
4. If adding another source mode later, extend resolver helpers instead of scattering conditionals.

## Suggested future hardening
1. Add lightweight unit/integration tests for relationship value normalization and query behavior.
2. Add a user-facing label setting for the dropdown (for example, Service Type instead of Type).
