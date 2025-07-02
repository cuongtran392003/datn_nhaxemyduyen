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
        whitecustom: "#FFFFFF",
        blackcustom: "#000000",
        orangecustom: "#F48124",
        bluecustom: "#5A4FF3",
        yellow: "#F3E600",
        red: "#BE0101",
        gray: "828282",
      },
      fontSize: {
        textmobileh1: "14px",
        textmobile: "10px",
        textdesktop: "43px",
      },
      animation: {
        fadeIn: "fadeIn 0.5s ease-out",
        slideIn: "slideIn 0.5s ease-out",
        bounceIn: "bounceIn 0.6s ease-out",
        pulse: "pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite",
        bounce: "bounce 1s infinite",
        selectedPulse: "selectedPulse 1.5s ease-in-out infinite",
        floating: "floating 6s ease-in-out infinite",
        spin: "spin 1s linear infinite",
      },
      keyframes: {
        fadeIn: {
          from: {
            opacity: "0",
            transform: "translateY(10px)",
          },
          to: {
            opacity: "1",
            transform: "translateY(0)",
          },
        },
        slideIn: {
          from: {
            opacity: "0",
            transform: "translateX(-20px)",
          },
          to: {
            opacity: "1",
            transform: "translateX(0)",
          },
        },
        bounceIn: {
          "0%": {
            opacity: "0",
            transform: "scale(0.3) translateY(-50px)",
          },
          "50%": {
            opacity: "1",
            transform: "scale(1.1) translateY(0)",
          },
          "100%": {
            opacity: "1",
            transform: "scale(1) translateY(0)",
          },
        },
        selectedPulse: {
          "0%": {
            boxShadow: "0 0 0 0 rgba(34, 197, 94, 0.7)",
          },
          "70%": {
            boxShadow: "0 0 0 10px rgba(34, 197, 94, 0)",
          },
          "100%": {
            boxShadow: "0 0 0 0 rgba(34, 197, 94, 0)",
          },
        },
        floating: {
          "0%": { transform: "translate(0, 0px)" },
          "50%": { transform: "translate(0, -15px)" },
          "100%": { transform: "translate(0, 0px)" },
        },
      },
      backdropBlur: {
        xs: "2px",
      },
    },
  },
  plugins: [],
};
