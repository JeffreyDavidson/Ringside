const mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for the application as well as bundling up all the JS files.
 |
 */

mix.js("resources/js/app.js", "public/js")
    .styles(
        [
            "resources/css/theme/style.bundle.css",
            "resources/css/theme/skins/header/base/light.css",
            "resources/css/theme/skins/header/menu/light.css",
            "resources/css/theme/skins/header/brand/dark.css",
            "resources/css/theme/skins/header/aside/dark.css"
        ],
        "public/css/theme.css"
    )
    .sass("resources/sass/app.scss", "public/css");
