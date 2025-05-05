// Ensure this file contains only valid JavaScript code.
// Example: BackToTop component implementation.

import React, { useState, useEffect } from 'react';

const BackToTop = () => {
    const [show, setShow] = useState(false);

    useEffect(() => {
        const scrollCallBack = () => {
            const scrolledFromTop = window.scrollY;
            setShow(scrolledFromTop > 300);
        };

        window.addEventListener('scroll', scrollCallBack);
        return () => window.removeEventListener('scroll', scrollCallBack);
    }, []);

    return (
      show && (
        <button
          className="w-12 h-12 transition-transform duration-200 flex fixed 
                right-10 bottom-2 bg-bluecustom text-white rounded-full shadow-lg
                 shadow-gray-900 justify-center items-center
                 z-10"
          onClick={() => window.scrollTo({ top: 0, behavior: "smooth" })}
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            strokeWidth="1.5"
            stroke="currentColor"
            className="size-6"
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M8.25 6.75 12 3m0 0 3.75 3.75M12 3v18"
            />
          </svg>
        </button>
      )
    );
};

export default BackToTop;
