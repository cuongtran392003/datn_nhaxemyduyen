// Ensure this file contains only valid JavaScript code.
// Example: BackToTop component implementation.

import React, { useState, useEffect } from 'react';
import './BackToTop.css';

const BackToTop = () => {
    const [show, setShow] = useState(false);
    const [scrollProgress, setScrollProgress] = useState(0);

    useEffect(() => {
        const scrollCallBack = () => {
            const scrolledFromTop = window.scrollY;
            const totalDocumentHeight = document.documentElement.scrollHeight - window.innerHeight;
            const progress = (scrolledFromTop / totalDocumentHeight) * 100;
            
            setShow(scrolledFromTop > 300);
            setScrollProgress(progress);
        };

        window.addEventListener('scroll', scrollCallBack);
        return () => window.removeEventListener('scroll', scrollCallBack);
    }, []);

    const scrollToTop = () => {
        window.scrollTo({ 
            top: 0, 
            behavior: "smooth" 
        });
    };

    return (        <div className={`back-to-top-container fixed right-3 md:right-6 bottom-3 md:bottom-6 z-50 transition-all duration-500 ease-out ${
            show ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-8 pointer-events-none'
        }`}>
            {/* Progress Ring */}
            <div className="relative group">                <svg 
                    className="progress-circle w-10 h-10 md:w-12 md:h-12 transform -rotate-90 absolute top-0 left-0"
                    viewBox="0 0 100 100"
                >                    {/* Background Circle */}
                    <circle
                        cx="50"
                        cy="50"
                        r="42"
                        stroke="rgba(59, 130, 246, 0.15)"
                        strokeWidth="6"
                        fill="transparent"
                        className="transition-all duration-300"
                    />
                    {/* Progress Circle - Loading Effect */}
                    <circle
                        cx="50"
                        cy="50"
                        r="42"
                        stroke="url(#gradient)"
                        strokeWidth="6"
                        fill="transparent"
                        strokeLinecap="round"
                        strokeDasharray={`${2 * Math.PI * 42}`}
                        strokeDashoffset={`${2 * Math.PI * 42 - (scrollProgress / 100) * 2 * Math.PI * 42}`}
                        className="transition-all duration-150 ease-out"
                        style={{
                            filter: `drop-shadow(0 0 ${scrollProgress / 20 + 2}px rgba(59, 130, 246, 0.6))`
                        }}
                    />
                    {/* Gradient Definition */}
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" stopColor="#3b82f6" />
                            <stop offset="50%" stopColor="#8b5cf6" />
                            <stop offset="100%" stopColor="#ec4899" />
                        </linearGradient>
                    </defs>
                </svg>
                
                {/* Main Button */}                <button
                    onClick={scrollToTop}
                    className="back-to-top-btn relative w-10 h-10 md:w-12 md:h-12 bg-white hover:bg-gray-50 
                             rounded-full shadow-lg hover:shadow-xl
                             flex items-center justify-center
                             transform transition-all duration-300 ease-out
                             hover:scale-105 active:scale-95
                             border border-gray-200
                             group-hover:bg-gradient-to-br group-hover:from-blue-500 group-hover:to-purple-600
                             group-hover:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-1"
                    aria-label="Quay lên đầu trang"
                >
                    {/* Arrow Icon */}                    <svg
                        className="arrow-icon w-4 h-4 md:w-5 md:h-5 text-gray-600 group-hover:text-white 
                                 transition-all duration-300 transform group-hover:-translate-y-0.5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="2.5"
                            d="M5 10l7-7m0 0l7 7m-7-7v18"
                        />
                    </svg>
                      {/* Loading Pulse Effect */}
                    <span 
                        className="absolute inset-0 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 
                                   opacity-0 transition-all duration-300 ease-out"
                        style={{
                            opacity: scrollProgress > 0 ? scrollProgress / 500 : 0,
                            transform: `scale(${1 + scrollProgress / 1000})`
                        }}
                    ></span>
                </button>
                  {/* Compact Tooltip */}
                <div className="tooltip absolute right-full mr-2 top-1/2 transform -translate-y-1/2
                              opacity-0 group-hover:opacity-100 transition-all duration-300
                              pointer-events-none hidden md:block">
                    <div className="bg-gray-800 text-white text-xs px-2 py-1 rounded-md
                                  shadow-lg whitespace-nowrap relative">
                        Lên đầu
                        <div className="absolute left-full top-1/2 transform -translate-y-1/2">
                            <div className="border-2 border-transparent border-l-gray-800"></div>
                        </div>
                    </div>
                </div>
            </div>
              {/* Floating Animation Particles */}            {/* Simplified Particles */}
            <div className="absolute inset-0 pointer-events-none">
                <div className="particle absolute w-1 h-1 bg-blue-400 rounded-full opacity-0 
                              group-hover:opacity-50 group-hover:animate-ping
                              -top-1 -right-1
                              transition-all duration-300 delay-100"></div>
                <div className="particle absolute w-1 h-1 bg-purple-400 rounded-full opacity-0 
                              group-hover:opacity-40 group-hover:animate-ping
                              -bottom-1 -left-1
                              transition-all duration-300 delay-200"></div>
            </div>
        </div>
    );
};

export default BackToTop;
