const mix = require("laravel-mix");

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

mix.scripts(["resources/vendors/jquery/dist/jquery.js"], "public/js/vendors.js")
    .styles(
        [
            "resources/css/theme/style.bundle.css",
            "resources/css/theme/skins/header/base/light.css",
            "resources/css/theme/skins/header/menu/light.css",
            "resources/css/theme/skins/brand/dark.css",
            "resources/css/theme/skins/aside/dark.css"
        ],
        "public/css/theme.css"
    )
    .scripts(["resources/js/scripts.bundle.js"], "public/js/app.js")
    .styles(
        [
            "resources/vendors/flaticon/flaticon.css",
            "resources/vendors/flaticon2/flaticon.css"
        ],
        "public/css/vendors.css"
    )
    .copyDirectory("resources/vendors/flaticon/font", "public/css/font")
    .copyDirectory("resources/vendors/flaticon2/font", "public/css/font")
    .sass("resources/sass/app.scss", "public/css");
