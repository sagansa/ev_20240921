import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Figtree", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                "ev-blue": {
                    100: "#cce4ff",
                    400: "#3395ff",
                    800: "#004a99",
                },
                "ev-green": {
                    100: "#d4edda",
                    400: "#5dd879",
                    800: "#1e7e34",
                },
                "ev-gray": {
                    100: "#f1f3f5",
                    400: "#adb5bd",
                    800: "#343a40",
                },
                "ev-white": "#ffffff",
                "ev-black": "#000000",
            },
        },
    },

    plugins: [forms, typography],
};
