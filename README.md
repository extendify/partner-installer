# Company Partnerships

## Notice + Installer
To use, add the following anywhere in your project that makes sense to load dependencies.
```php
$project_name = 'My Project';


// Plugins:
require_once plugin_dir_path( __FILE__ ) . '/class-partnership-notice.php';
new Extendify_Partner( $project_name, $logo, array( 'plugins' ), $labels );

// Themes:
require_once get_template_directory() . '/class-partnership-notice.php';
new Extendify_Partner( $project_name, $logo, array( 'themes' ), $labels );
 ```

 To add your logo, you can use either html string or an array
```php
$logo = '<svg width="128" height="128">...</svg>';
// or
$logo = array(
    'img'    => 'https://your-url.com/image.png',
    'width'  => 128,
    'height' => 128,
);
```

To update labels, add the following:

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
