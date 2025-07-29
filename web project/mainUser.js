
"use strict";

function initPageFadeIn() {
  document.body.style.opacity = 0;
  document.body.style.transition = "opacity 0.8s ease-in-out";
  window.addEventListener("load", () => {
    document.body.style.opacity = 1;
  });
}

function initInputFocusEffects() {
  const fields = document.querySelectorAll("input, textarea");
  fields.forEach((field) => {
    field.addEventListener("focus", () => {
      field.style.transition = "box-shadow 0.3s, border-color 0.3s";
      field.style.boxShadow = "0 0 6px rgba(211, 47, 47, 0.7)";
      field.style.borderColor = "#d32f2f";
    });
    field.addEventListener("blur", () => {
      field.style.boxShadow = "none";
      field.style.borderColor = "";
    });
  });
}

function initButtonHoverEffects() {
  const buttons = document.querySelectorAll("button, input[type='submit']");
  buttons.forEach((btn) => {
    btn.addEventListener("mouseenter", () => {
      btn.style.transition = "transform 0.2s ease-in-out";
      btn.style.transform = "scale(1.05)";
    });
    btn.addEventListener("mouseleave", () => {
      btn.style.transform = "scale(1)";
    });
  });
}

function initAOS() {
  if (typeof AOS !== "undefined") {
    AOS.init({
      duration: 800,
      once: true,
      easing: "ease-in-out"
    });
  } else {
    console.warn("AOS library not loaded.");
  }
}

function initDarkModeToggle() {
  const themeToggle = document.getElementById("theme-toggle");
  const savedTheme = localStorage.getItem("theme");

  function applyTheme(theme) {
    document.documentElement.setAttribute("data-theme", theme);
    localStorage.setItem("theme", theme);
  }

  if (savedTheme === "dark") {
    applyTheme("dark");
    themeToggle.checked = true;
  } else {
    applyTheme("light");
    themeToggle.checked = false;
  }

  themeToggle.addEventListener("change", () => {
    if (themeToggle.checked) {
      applyTheme("dark");
    } else {
      applyTheme("light");
    }
  });
}

function initUserAnimations() {
  initPageFadeIn();
  initInputFocusEffects();
  initButtonHoverEffects();
  initAOS();
  initDarkModeToggle();
}

document.addEventListener("DOMContentLoaded", initUserAnimations);
document.addEventListener('DOMContentLoaded', () => {
    const themeToggle = document.getElementById('theme-toggle');
    const body = document.body; // Or document.documentElement for <html>

    // Function to apply the theme
    function applyTheme(theme) {
        if (theme === 'dark') {
            body.setAttribute('data-theme', 'dark');
            if (themeToggle) themeToggle.checked = true;
        } else {
            body.setAttribute('data-theme', 'light'); // Explicitly set light
            if (themeToggle) themeToggle.checked = false;
        }
    }

    // Check localStorage for saved theme
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        applyTheme(savedTheme);
    } else {
        // Check system preference if no theme is saved
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (prefersDark) {
            applyTheme('dark');
            // localStorage.setItem('theme', 'dark'); // Optionally save system preference on first load
        } else {
            applyTheme('light'); // Default to light if no preference or saved theme
        }
    }

    // Event listener for the toggle switch
    if (themeToggle) {
        themeToggle.addEventListener('change', function() {
            if (this.checked) {
                applyTheme('dark');
                localStorage.setItem('theme', 'dark');
            } else {
                applyTheme('light');
                localStorage.setItem('theme', 'light');
            }
        });
    }

    // Initialize AOS if it's present on the page
    if (typeof AOS !== 'undefined') {
        AOS.init({
            duration: 800, // values from 0 to 3000, with step 50ms
            once: true,    // whether animation should happen only once - while scrolling down
        });
    }
});