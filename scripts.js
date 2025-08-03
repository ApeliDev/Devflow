// Animation on scroll
const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
};

const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
        }
    });
}, observerOptions);

// Observe all animation elements
document.querySelectorAll('.fade-in, .slide-in-left, .slide-in-right, .scale-in, .rotate-in').forEach(el => {
    observer.observe(el);
});

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href').substring(1);
        const targetElement = document.getElementById(targetId);
        if (targetElement) {
            targetElement.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            
            // Close mobile menu if open
            if (document.getElementById('mobileMenu').classList.contains('open')) {
                toggleMobileMenu();
            }
        }
    });
});

// Floating particles animation
function createParticle() {
    const particle = document.createElement('div');
    particle.className = 'particle';
    particle.style.left = Math.random() * 100 + 'vw';
    particle.style.animationDelay = Math.random() * 4 + 's';
    particle.style.animationDuration = (Math.random() * 3 + 2) + 's';
    particle.style.width = (Math.random() * 6 + 2) + 'px';
    particle.style.height = particle.style.width;
    particle.style.backgroundColor = `hsl(${Math.random() * 60 + 30}, 100%, 50%)`;
    document.getElementById('particles').appendChild(particle);
    
    setTimeout(() => {
        particle.remove();
    }, 5000);
}

// Create particles periodically
setInterval(createParticle, 800);

// Modal functions
function openModal() {
    const modal = document.getElementById('projectModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('visible');
    }, 10);
    document.body.style.overflow = 'hidden';
}

function closeModal() {
    const modal = document.getElementById('projectModal');
    modal.classList.remove('visible');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }, 300);
}

function openSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.classList.remove('hidden');
    setTimeout(() => {
        modal.classList.add('visible');
    }, 10);
    document.body.style.overflow = 'hidden';
    
    // Create confetti effect
    createConfetti();
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.classList.remove('visible');
    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
    }, 300);
}

// Close modal when clicking outside
document.getElementById('projectModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeModal();
    }
});

document.getElementById('successModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSuccessModal();
    }
});

// Navbar background on scroll
window.addEventListener('scroll', function() {
    const navbar = document.querySelector('nav');
    if (window.scrollY > 50) {
        navbar.classList.add('bg-white');
        navbar.classList.remove('bg-white/90');
    } else {
        navbar.classList.add('bg-white/90');
        navbar.classList.remove('bg-white');
    }
});

// Mobile menu toggle
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileMenuButton = document.getElementById('mobileMenuButton');
    
    mobileMenu.classList.toggle('open');
    
    if (mobileMenu.classList.contains('open')) {
        mobileMenuButton.innerHTML = '<i class="bi bi-x-lg text-2xl"></i>';
        document.body.style.overflow = 'hidden';
    } else {
        mobileMenuButton.innerHTML = '<i class="bi bi-list text-2xl"></i>';
        document.body.style.overflow = 'auto';
    }
}

document.getElementById('mobileMenuButton').addEventListener('click', toggleMobileMenu);

// Confetti effect
function createConfetti() {
    const colors = ['#FF5252', '#FFD740', '#64FFDA', '#448AFF', '#B388FF'];
    const container = document.getElementById('confettiContainer');
    container.innerHTML = '';
    
    // Create a single style element for all keyframes
    const style = document.createElement('style');
    document.head.appendChild(style);
    
    for (let i = 0; i < 100; i++) {
        const confetti = document.createElement('div');
        confetti.className = 'confetti';
        confetti.style.left = Math.random() * 100 + '%';
        confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
        confetti.style.width = (Math.random() * 10 + 5) + 'px';
        confetti.style.height = (Math.random() * 10 + 5) + 'px';
        confetti.style.opacity = '1';
        
        // Random shape
        if (Math.random() > 0.5) {
            confetti.style.borderRadius = '50%';
        } else {
            confetti.style.transform = `rotate(${Math.random() * 360}deg)`;
        }
        
        // Unique animation name for each confetti
        const animationName = `confetti-fall-${i}`;
        confetti.style.animation = `${animationName} ${Math.random() * 3 + 2}s linear forwards`;
        
        // Add keyframe to style element
        style.sheet.insertRule(`
            @keyframes ${animationName} {
                0% {
                    transform: translateY(-100vh) rotate(0deg);
                    opacity: 1;
                }
                100% {
                    transform: translateY(100vh) rotate(${Math.random() * 360}deg);
                    opacity: 0;
                }
            }
        `, style.sheet.cssRules.length);
        
        container.appendChild(confetti);
    }
}

// Initialize animations on page load
document.addEventListener('DOMContentLoaded', function() {
    // Add initial delay to hero animations
    setTimeout(() => {
        document.querySelectorAll('#home .fade-in').forEach(el => {
            el.classList.add('visible');
        });
    }, 500);
    
    // Add hover effects to elements
    document.querySelectorAll('.hover-lift').forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px)';
            this.style.transition = 'transform 0.2s ease';
        });
        
        element.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Add click effect to buttons
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('mousedown', function() {
            this.style.transform = 'scale(0.95)';
        });
        
        button.addEventListener('mouseup', function() {
            this.style.transform = 'scale(1)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
    
    // Close mobile menu when clicking a link
    document.querySelectorAll('#mobileMenu a').forEach(link => {
        link.addEventListener('click', toggleMobileMenu);
    });
});