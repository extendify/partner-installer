# Extendify Notice
This code will add a notice when a plugin or theme is activated. The notice will recommend that the Extendify plugin is optionally installed from the WordPress.org servers. The notice will appear on either the theme or plugin install page. If dismissed, the notice will never be displayed again to that user.

**Steps:**

1) Add the code in this repo to your plugin or theme
2) Add the loader code snippet below
3) Change the project_name to the name of your plugin or theme
4) Add your logo using either method described below

## Loader

Add the following anywhere in your project that makes sense to load dependencies:
```php
$project_name = 'My Project';


// Plugins:
require_once plugin_dir_path( __FILE__ ) . '/class-partnership-notice.php';
new Extendify_Partner( $project_name, $logo, array( 'plugins' ), $labels );

// Themes:
require_once get_template_directory() . '/class-partnership-notice.php';
new Extendify_Partner( $project_name, $logo, array( 'themes' ), $labels );
 ```
 
_Note: Change `My Project` to the name of your plugin or theme._

## Logo

 To add your logo, you can use either an html string or an array:
```php
$logo = '<svg width="128" height="128">...</svg>';
// or
$logo = array(
    'img'    => 'https://your-url.com/image.png',
    'width'  => 128,
    'height' => 128,
);
```

## Customize

You can customize the content of the notice if you wish. To update labels, add the following:

```php
$labels = array(
    'header'        => esc_html__( '', 'TEXT_DOMAIN' ),
    'main_content'  => esc_html__( '', 'TEXT_DOMAIN' ),
    'install'       => esc_html__( '', 'TEXT_DOMAIN' ),
    'installing'    => esc_html__( '', 'TEXT_DOMAIN' ),
    'reloading'     => esc_html__( '', 'TEXT_DOMAIN' ),
    'dismiss_label' => esc_attr__( '', 'TEXT_DOMAIN' ),
);
 ```
