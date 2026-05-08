# Portal Manager Core Content Permissions (Implementation + Rollback Guide)

Status: Implemented
Last updated: 2026-05-08
Owner: RW Dealer Portal developers

## Goal
Add a new Dealer Portal Settings tab that lets administrators control whether users with the `rwdp_portal_manager` role can manage WordPress core Posts, Pages, and Media.

The tab provides two checkboxes:
1. Allow add/edit Posts, Pages, Media.
2. Allow delete Posts, Pages, Media.

## Scope
Included:
1. Settings UI and persistence in `rwdp_settings`.
2. Runtime permission gating for `rwdp_portal_manager` in access-control logic.
3. Menu/admin-bar visibility alignment with selected settings.

Excluded:
1. New roles.
2. Per-user overrides.
3. Changes to dealer and asset CPT permissions.

## Implemented changes snapshot
1. Added `portal_manager_access` tab in settings UI.
2. Added settings keys in `rwdp_settings`:
- `portal_manager_allow_core_content_manage`
- `portal_manager_allow_core_content_delete`
3. Added runtime helper in access control:
- `rwdp_get_portal_manager_core_content_permissions()`
4. Updated `rwdp_restrict_portal_manager_caps()` to conditionally grant or block core Posts/Pages/Media capabilities based on settings.
5. Updated `rwdp_restrict_portal_manager_menus()` and `rwdp_clean_portal_manager_admin_bar()` so menu/admin-bar behavior follows the manage toggle.

## Implementation map

### 1) Settings tab and options
File: `includes/admin-settings.php`

Add options:
1. `portal_manager_allow_core_content_manage` (bool)
2. `portal_manager_allow_core_content_delete` (bool)

Implementation notes:
1. Extend `valid_tabs` and `tabs` with a new tab (for example `portal_manager_access`).
2. Render checkbox fields with clear descriptions.
3. Add hidden field preservation for these options when saving from other tabs.
4. Sanitization rules:
- Treat checkboxes as booleans (`1` or `0`).
- Force delete option to `0` when manage option is `0`.

### 2) Capability gating
File: `includes/access-control.php`

Update `rwdp_restrict_portal_manager_caps()`:
1. Keep these always blocked for Portal Manager:
- `manage_options`
- plugin install/update/activate caps
- theme option caps
2. Conditionally allow/block core content caps based on settings:
- Manage = off: block create/edit/publish/upload/delete for posts/pages/media.
- Manage = on, Delete = off: allow create/edit/publish/upload, block delete caps.
- Manage = on, Delete = on: allow create/edit/publish/upload/delete.

Recommended cap groups:
1. Manage group (posts/pages/media)
- `edit_posts`, `edit_others_posts`, `publish_posts`
- `edit_pages`, `edit_others_pages`, `publish_pages`
- `upload_files`
2. Delete group (posts/pages/media)
- `delete_posts`, `delete_others_posts`, `delete_published_posts`
- `delete_pages`, `delete_others_pages`, `delete_published_pages`
- attachment delete caps as applicable in your WP version

### 3) Menu/admin-bar alignment
File: `includes/access-control.php`

Update `rwdp_restrict_portal_manager_menus()` and admin bar cleanup:
1. If Manage = off, keep Posts and Pages menus removed.
2. If Manage = on, leave Posts and Pages menus visible.
3. Keep Plugins, Themes, Settings, Tools, Appearance removed regardless.
4. Only remove `new-content` admin-bar node when Manage = off.

## Testing matrix

### Base safety
1. Portal Manager never gets Plugins/Themes/Settings/Tools access.
2. Existing Dealer/Asset workflows still work.

### Permission scenarios
1. Manage=0 Delete=0:
- Cannot add/edit/delete posts, pages, media.
2. Manage=1 Delete=0:
- Can add/edit posts/pages and upload media.
- Cannot delete posts/pages/media.
3. Manage=1 Delete=1:
- Can add/edit/delete posts/pages/media.

## Rollback instructions

Use this rollback when you need to fully remove this feature after implementation.

### Code rollback
1. In `includes/admin-settings.php`:
- Remove tab slug/label and tab rendering block.
- Remove hidden preservation inputs for the two settings keys.
- Remove sanitization handling for:
  - `portal_manager_allow_core_content_manage`
  - `portal_manager_allow_core_content_delete`
2. In `includes/access-control.php`:
- Remove helper that reads these settings.
- Restore hard-block behavior in `rwdp_restrict_portal_manager_caps()`.
- Restore unconditional Posts/Pages menu removal and admin-bar new-content removal for Portal Manager.

### Data rollback (optional)
1. Clean the two keys from `rwdp_settings` in the database.
2. No role migration is required.
3. No rewrite flush is required.

## Suggested commit strategy
1. Commit 1: settings tab + sanitization only.
2. Commit 2: capability gating + menu/admin-bar behavior.
3. Commit 3: docs and QA notes.

This split makes git revert simple and low risk.

## Suggested PR checklist
1. Added/updated docs (`CHANGELOG-DEV.md`, this file).
2. Verified all three permission scenarios.
3. Confirmed no new admin privilege escalation.
4. Confirmed no impact to Dealer/Asset custom post type workflows.
