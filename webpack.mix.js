const mix = require("laravel-mix");
const endsWith = require("lodash.endswith");
const glob = require("glob");
const output = require("friendly-errors-webpack-plugin/src/output");

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

mix.styles(
    [
        "resources/css/theme/style.bundle.css",
        "resources/css/theme/skins/header/base/light.css",
        "resources/css/theme/skins/header/menu/light.css",
        "resources/css/theme/skins/brand/dark.css",
        "resources/css/theme/skins/aside/dark.css"
    ],
    "public/css/theme.css"
);

mix.styles(
    [
        "resources/vendors/flaticon/flaticon.css",
        "resources/vendors/flaticon2/flaticon.css"
    ],
    "public/css/fonts.css"
);
mix.sass("resources/sass/app.scss", "public/css");

/************************************************************************
 * Copy Static Assets
 ************************************************************************/
mix.copyDirectory("resources/vendors/flaticon/font", "public/css/font");
mix.copyDirectory("resources/vendors/flaticon2/font", "public/css/font");

/************************************************************************
 * Mix Configuration
 * * We aren't keen on the "Build successful" notifications, so disable
 *   them
 * * We load in our webpack.config.js, which just contains aliases so
 *   PHPstorm reads them
 ************************************************************************/
// mix.webpackConfig(require("./webpack.config"));
mix.disableSuccessNotifications();
mix.sourceMaps(false, "inline-source-map");

/************************************************************************
 * Spit out all our entries as individual files
 * * specifically it's anything in resources/js/entries/*
 *   or any child directories, that get made into their own files
 * * It could be simpler, but we also make it output a line in the console
 *   for each entry.
 ************************************************************************/
/** @var {String[]} */
const entries = glob.sync("resources/js/entries/**/*.js");
const buildOutputPath = file =>
    file.replace("resources/js/entries/", "public/js/");
entries
    .filter(entry => !endsWith(entry, "app.js"))
    .forEach(file => {
        output.title("info", "ENTRY", file);
        mix.js(file, buildOutputPath(file));
    });
// We need to manually run this last as otherwise our output path breaks.
mix.js("resources/js/entries/app.js", "public/js/app.js");

/************************************************************************
 * Extract all the vendor files, these are anything that appear in more
 * than ~3 of our entries. These all end up in vendor.js
 ************************************************************************/
mix.extract([
    "jquery",
    "popper.js",
    "bootstrap",
    "js-cookie",
    "moment",
    "tooltip.js",
    "perfect-scrollbar",
    "sticky-js",
    "wnumb",
    "datatables",
    "block-ui",
    "bootstrap-select"
]);
