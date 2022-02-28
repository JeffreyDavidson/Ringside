const mix = require("laravel-mix");

mix.js("resources/js/app.js", "public/assets/js")

mix.copy([
    "resources/js/scripts.bundle.js",
    "resources/js/widgets.bundle.js",
], "public/assets/js");

mix.copy("resources/js/plugins.bundle.js", "public/assets/plugins/global");

mix.copy([
    "resources/js/custom/widgets.js",
    "resources/js/custom/tags.js",
], "public/assets/js/custom");

mix.copy("resources/css/plugins.bundle.css", "public/assets/plugins/global")
mix.copy("resources/css/style.bundle.css", "public/assets/css")
