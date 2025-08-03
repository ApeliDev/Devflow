<nav class="fixed top-0 w-full bg-white/90 backdrop-blur-sm z-50 border-b border-gray-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <h1 class="text-2xl font-bold" style="color: var(--green);">DevFlow Studio</h1>
                    </div>
                </div>
                <div class="hidden md:block">
                    <div class="ml-10 flex items-baseline space-x-8">
                        <a href="#home" class="hover:text-green-600 transition-colors flex items-center">
                            <i class="bi bi-house-door mr-2"></i> Home
                        </a>
                        <a href="#about" class="hover:text-green-600 transition-colors flex items-center">
                            <i class="bi bi-info-circle mr-2"></i> About
                        </a>
                        <a href="#features" class="hover:text-green-600 transition-colors flex items-center">
                            <i class="bi bi-stars mr-2"></i> Features
                        </a>
                        <a href="#testimonials" class="hover:text-green-600 transition-colors flex items-center">
                            <i class="bi bi-chat-square-quote mr-2"></i> Testimonials
                        </a>
                        <button onclick="openModal()" class="gold-gradient text-black px-6 py-2 rounded-full font-semibold hover:shadow-lg transition-all transform hover:scale-105 flex items-center">
                            <i class="bi bi-lightning-charge mr-2"></i> Start Project
                        </button>
                    </div>
                </div>
                <div class="md:hidden">
                    <button id="mobileMenuButton" class="text-gray-700 hover:text-green-600 focus:outline-none">
                        <i class="bi bi-list text-2xl"></i>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile Menu -->
        <div id="mobileMenu" class="mobile-menu md:hidden bg-white">
            <div class="px-4 pt-2 pb-4 space-y-2">
                <a href="#home" class="block px-3 py-2 rounded-md hover:bg-gray-100 transition-colors flex items-center">
                    <i class="bi bi-house-door mr-2"></i> Home
                </a>
                <a href="#about" class="block px-3 py-2 rounded-md hover:bg-gray-100 transition-colors flex items-center">
                    <i class="bi bi-info-circle mr-2"></i> About
                </a>
                <a href="#features" class="block px-3 py-2 rounded-md hover:bg-gray-100 transition-colors flex items-center">
                    <i class="bi bi-stars mr-2"></i> Features
                </a>
                <a href="#testimonials" class="block px-3 py-2 rounded-md hover:bg-gray-100 transition-colors flex items-center">
                    <i class="bi bi-chat-square-quote mr-2"></i> Testimonials
                </a>
                <button onclick="openModal()" class="w-full gold-gradient text-black px-4 py-2 rounded-full font-semibold hover:shadow-lg transition-all transform hover:scale-105 flex items-center justify-center">
                    <i class="bi bi-lightning-charge mr-2"></i> Start Project
                </button>
            </div>
        </div>
    </nav>