
// function googleTranslateElementInit() {
//     new google.translate.TranslateElement({
//         pageLanguage: 'en',
//         includedLanguages: 'en,my',
//         autoDisplay: false
//     }, 'google_translate_element');
// }

// function translateToMyanmar() {
//     var attempts = 0;
//     function tryTranslate() {
//         var combo = document.querySelector('.goog-te-combo');
//         if (combo) {
//             combo.value = 'my';
//             combo.dispatchEvent(new Event('change', {bubbles: true}));
//             return;
//         }
//         attempts++;
//         if (attempts < 60) setTimeout(tryTranslate, 200);
//     }
//     tryTranslate();
// }

// function restoreEnglish() {
//     var combo = document.querySelector('.goog-te-combo');
//     if (combo) {
//         combo.value = 'en';
//         combo.dispatchEvent(new Event('change', {bubbles: true}));
//     }
//     document.body.style.top = '0px';
//     document.body.style.position = '';
// }

// window.switchLang = function(lang) {
//     localStorage.setItem('site_lang', lang);
//     if (lang === 'my') {
//         translateToMyanmar();
//     } else {
//         restoreEnglish();
//     }
//     var label = document.getElementById('currentLangLabel');
//     if (label) {
//         label.textContent = lang === 'my' ? 'MM' : 'EN';
//     }
//     var dropdown = document.getElementById('langDropdown');
//     if (dropdown) {
//         dropdown.classList.add('hidden');
//     }
//     if (typeof updateLangUI === 'function') {
//         updateLangUI(lang);
//     }
// };

// /* Auto-restore saved language on page load */
// document.addEventListener('DOMContentLoaded', function() {
//     var saved = localStorage.getItem('site_lang');
//     if (saved === 'my') {
//         setTimeout(function() { translateToMyanmar(); }, 3000);
//     }
//     if (typeof updateLangUI === 'function') {
//         updateLangUI(saved || 'en');
//     }
// });

function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: "en",
        includedLanguages: "en,my",
        autoDisplay: false
    }, "google_translate_element");
}

function setLanguage(lang) {

    localStorage.setItem("site_lang", lang);

    document.cookie = "googtrans=/en/" + lang + ";path=/";
    document.cookie = "googtrans=/en/" + lang + ";domain=" + location.hostname + ";path=/";

    let count = 0;

    const timer = setInterval(function () {

        const combo = document.querySelector(".goog-te-combo");

        if (combo) {

            combo.value = lang;
            combo.dispatchEvent(new Event("change"));

            clearInterval(timer);

            document.body.style.top = "0";
            document.body.style.position = "";

            const label = document.getElementById("currentLangLabel");
            if (label) {
                label.innerText = lang === "my" ? "MM" : "EN";
            }

            if (typeof updateLangUI === "function") {
                updateLangUI(lang);
            }
        }

        count++;

        if (count > 40) {
            clearInterval(timer);
        }

    }, 250);
}

window.switchLang = function (lang) {
    setLanguage(lang);

    const dropdown = document.getElementById("langDropdown");
    if (dropdown) dropdown.classList.add("hidden");
};

window.addEventListener("load", function () {

    const lang = localStorage.getItem("site_lang") || "en";

    setTimeout(function () {
        setLanguage(lang);
    }, 1000);

});