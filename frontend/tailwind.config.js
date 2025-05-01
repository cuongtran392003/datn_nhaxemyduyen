/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{js,jsx,ts,tsx}"],
  theme: {
    fontFamily: {
      poppins: ["Poppins", "sans-serif", "semibold"],
      montserrat: ["Montserrat", "sans-serif", "semibold"],
    },
    extend: {
      colors: {
        "whitecustom": "#FFFFFF",
        "blackcustom": "#000000",
        "orangecustom": "#F48124",
        "bluecustom": "#5A4FF3",
        "yellow": "#F3E600",
        "red": "#BE0101",
        "gray": "828282",
      },
      fontSize: {
        "textmobileh1":"14px",
        "textmobile":"10px",
        "textdesktop":"43px",
      },
    },
  },
  plugins: [],
};
