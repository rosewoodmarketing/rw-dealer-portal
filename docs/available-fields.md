# Available Fields

This document lists all available fields for **Dealer** and **Asset** post types, including their machine names and Elementor Dynamic tag availability.

---

## 🏪 Dealer Post Type (`rw_dealer`)

> **Purpose:** Stores individual dealer locations with contact information, address, and contact details.  
> **Elementor Support:** ✅ Full dynamic tag support for all contact fields

### Post Fields
| Display Label | Machine Name | Type | Elementor Dynamic Tag |
|---|---|---|---|
| Title | `post_title` | Text | — |
| Featured Image/Thumbnail | `_thumbnail_id` | Integer | — |

### Address Fields
| Display Label | Machine Name | Type | Elementor Dynamic Tag |
|---|---|---|---|
| Street Address | `_rwdp_address` | Text | `rwdp_dealer_address` (combined with city/state/zip) |
| City | `_rwdp_city` | Text | `rwdp_dealer_address` (combined) |
| State | `_rwdp_state` | Text | `rwdp_dealer_address` (combined) |
| ZIP Code | `_rwdp_zip` | Text | `rwdp_dealer_address` (combined) |

### Contact Fields
| Display Label | Machine Name | Type | Elementor Dynamic Tag |
|---|---|---|---|
| Phone | `_rwdp_phone` | Text | `rwdp_dealer_phone` |
| Phone Link | `_rwdp_phone` | Text | `rwdp_dealer_phone_link` (URL → `tel:`) |
| Website | `_rwdp_website` | Text (stored with `https://` prefix) | `rwdp_dealer_website` |
| Website URL | `_rwdp_website` | Text | `rwdp_dealer_website_url` (Data tag → URL) |
| Public Email | `_rwdp_public_email` | Email | `rwdp_dealer_email` |
| Request Email(s) | `_rwdp_contact_emails` | Text (comma-separated) | — |

### Additional Fields
| Display Label | Machine Name | Type | Elementor Dynamic Tag |
|---|---|---|---|
| Hours | `_rwdp_hours` | Text | `rwdp_dealer_hours` |
| Logo | `_rwdp_logo_id` | Integer (media ID) | `rwdp_dealer_logo` (Image tag) |
| Directions Link | Multiple (_rwdp_address, _rwdp_city, _rwdp_state, _rwdp_zip) | Computed URL | `rwdp_dealer_directions` (Data tag → URL + Text) |

### Geocoding Fields (Read-Only)
| Display Label | Machine Name | Type | Notes |
|---|---|---|---|
| Latitude | `_rwdp_lat` | Float | Set automatically by geocoding |
| Longitude | `_rwdp_lng` | Float | Set automatically by geocoding |
| Address Valid | `_rwdp_address_valid` | String ('0' or '1') | Indicates if address has been successfully geocoded |
| Geocoding Error | `_rwdp_geo_error` | String | Contains error code from Google Maps API if geocoding failed (e.g., REQUEST_DENIED, OVER_QUERY_LIMIT) |

---

## 📚 Asset Post Type (`rw_asset`)

> **Purpose:** Stores reusable content assets (PDFs, guides, documents) organized hierarchically.  
> **Elementor Support:** ❌ Not yet available for dynamic tags (Dealer-specific feature)

### Post Fields
| Display Label | Machine Name | Type | Elementor Dynamic Tag |
|---|---|---|---|
| Title | `post_title` | Text | — |
| Content | `post_content` | Text (WYSIWYG editor) | — |
| Featured Image/Thumbnail | `_thumbnail_id` | Integer | — |
| Parent Asset | `post_parent` | Integer (post ID) | — |
| Menu Order | `menu_order` | Integer | Used for custom ordering |

### Features & Notes
- **Hierarchical:** Assets can have parent-child relationships (nested up to any depth)
- **Ordering:** Use Menu Order for custom display order (lower numbers appear first)
- **Standard WordPress fields:** All post fields supported
- **Custom Meta Fields:** None registered yet (extensible)

---

## Elementor Dynamic Tags Summary

### Available Tags (9 total — Dealer Post Type Only)

> ⚠️ **All dynamic tags currently support only the Dealer post type (`rw_dealer`)**  
> Asset post type tags are not yet implemented.

| Tag Name | Machine Name | Output Type | Meta Keys Used | Post Type Support |
|---|---|---|---|---|
| Dealer Address | `rwdp_dealer_address` | Text | `_rwdp_address`, `_rwdp_city`, `_rwdp_state`, `_rwdp_zip` | `rw_dealer` |
| Dealer Phone | `rwdp_dealer_phone` | Text | `_rwdp_phone` | `rw_dealer` |
| Dealer Phone Link | `rwdp_dealer_phone_link` | URL | `_rwdp_phone` | `rw_dealer` |
| Dealer Website | `rwdp_dealer_website` | Text | `_rwdp_website` | `rw_dealer` |
| Dealer Website URL | `rwdp_dealer_website_url` | URL | `_rwdp_website` | `rw_dealer` |
| Dealer Email | `rwdp_dealer_email` | Text | `_rwdp_public_email` | `rw_dealer` |
| Dealer Hours | `rwdp_dealer_hours` | Text | `_rwdp_hours` | `rw_dealer` |
| Dealer Logo | `rwdp_dealer_logo` | Image | `_rwdp_logo_id` | `rw_dealer` |
| Dealer Directions Link | `rwdp_dealer_directions` | URL + Text | `_rwdp_address`, `_rwdp_city`, `_rwdp_state`, `_rwdp_zip` | `rw_dealer` |

### How to Use Dynamic Tags in Elementor
1. Add a widget that supports dynamic data (e.g., text, URL, image)
2. Click the **dynamic data button** (lightning bolt icon) next to the control
3. Select **RW Dealer Portal** group
4. Choose the desired tag
5. Tag will pull current post data automatically in the Loop/Single post editor

---

## Field Access Methods

### In Shortcodes/PHP Code

**🏪 For Dealer Posts:**
```php
// Get single field
$phone = get_post_meta( $post_id, '_rwdp_phone', true );

// Get multiple fields
$dealer_data = [
    'phone'   => get_post_meta( $post_id, '_rwdp_phone', true ),
    'website' => get_post_meta( $post_id, '_rwdp_website', true ),
    'email'   => get_post_meta( $post_id, '_rwdp_public_email', true ),
];
```

**📚 For Asset Posts:**
```php
// Standard WordPress post fields
$asset_title = get_the_title( $post_id );
$asset_content = get_the_content( '', false, $post_id );
$asset_parent = wp_get_post_parent_id( $post_id );
$asset_order = get_post_field( 'menu_order', $post_id );
```

### In Elementor Loop

> ⚠️ **Dynamic Tags only available for Dealer posts**

- Use **Dynamic Tags** in Loop Grid/Loop Carousel widgets
- Select **RW Dealer Portal** group for dealer fields
- Tags available in all text, URL, and image controls
- Prefix: `RW Dealer Portal` group

### In REST API

**🏪 Dealer Posts:**
```
GET /wp-json/wp/v2/rw_dealer/{id}
```
- All meta fields prefixed with `_rwdp_` are accessible
- Returns custom fields alongside standard post data

**📚 Asset Posts:**
```
GET /wp-json/wp/v2/rw_asset/{id}
```
- Standard WordPress post fields only
- Supports hierarchical queries with `parent` parameter
