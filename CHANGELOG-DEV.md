# RW Dealer Portal Dev Change Notes

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
3. Dealer Filter Taxonomy setting was removed from the UI.
4. Filter Source Mode now uses ACF relationship field mode.
5. Existing frontend AJAX contract was preserved (type_id and locked_type request keys, type_ids response key).
6. Dealer Finder settings show a warning when relationship mode is misconfigured.
7. Dealer Finder settings show an info notice when relationship mode is configured successfully.
8. Dealer Finder settings now include a diagnostic line showing detected filter option count from the configured relationship field.
9. Relationship option detection now scans all published dealers (not only geocoded dealers) so diagnostics reflect available field data earlier.
10. Relationship value parsing now supports multiple storage/return formats (objects, IDs, serialized arrays, formatted/unformatted ACF reads).

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

Revert options:
1. Full revert to previous taxonomy model:
- Remove relationship helper functions.
- Restore taxonomy term retrieval/query logic.
- Restore taxonomy-based type_ids payload logic.
2. Keep relationship helpers but disable feature:
- Force rwdp_get_dealer_filter_settings() to return enabled=false.

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

## Notes for AI agents
1. Preserve request/response keys used by assets/js/dealer-map.js unless intentionally versioning frontend behavior.
2. Keep settings additive inside rwdp_settings.
3. Avoid introducing hard ACF dependency in bootstrap/activation flow.
4. If adding another source mode later, extend resolver helpers instead of scattering conditionals.

## Suggested future hardening
1. Add lightweight unit/integration tests for relationship value normalization and query behavior.
2. Add a user-facing label setting for the dropdown (for example, Service Type instead of Type).
