document.addEventListener("DOMContentLoaded", function () {

    /* ===========================
       FAQ
    =========================== */

    const questions = document.querySelectorAll(".faq-question");

    questions.forEach(function(question){

        question.addEventListener("click", function(){

            const answer = question.nextElementSibling;

            answer.classList.toggle("show");

            const symbol = question.querySelector("span");

            if(symbol){
                symbol.textContent = answer.classList.contains("show") ? "−" : "+";
            }

        });

    });


    /* ===========================
       MODAL
    =========================== */

    const openModal = document.getElementById("openModal");
    const closeModal = document.getElementById("closeModal");
    const studentModal = document.getElementById("studentModal");

    if(openModal && closeModal && studentModal){

        openModal.addEventListener("click", function(){

            studentModal.classList.add("show");

        });

        closeModal.addEventListener("click", function(){

            studentModal.classList.remove("show");

        });

        window.addEventListener("click", function(event){

            if(event.target === studentModal){

                studentModal.classList.remove("show");

            }

        });

    }


    /* ===========================
       SLIDER
    =========================== */

    const slides = document.querySelectorAll(".slide");
    const next = document.getElementById("next");
    const prev = document.getElementById("prev");

    if(slides.length > 0 && next && prev){

        let current = 0;

        function showSlide(index){

            slides.forEach(function(slide){

                slide.classList.remove("active");

            });

            slides[index].classList.add("active");

        }

        showSlide(current);

        next.addEventListener("click", function(){

            current++;

            if(current >= slides.length){

                current = 0;

            }

            showSlide(current);

        });

        prev.addEventListener("click", function(){

            current--;

            if(current < 0){

                current = slides.length - 1;

            }

            showSlide(current);

        });

    }


    /* ===========================
       DARK MODE
    =========================== */

    const themeBtn = document.getElementById("themeBtn");

    if(localStorage.getItem("theme") === "dark"){

        document.body.classList.add("dark-mode");

        if(themeBtn){
            themeBtn.textContent = "☀ Light Mode";
        }

    }

    if(themeBtn){

        themeBtn.addEventListener("click", function(){

            document.body.classList.toggle("dark-mode");

            if(document.body.classList.contains("dark-mode")){

                localStorage.setItem("theme","dark");
                themeBtn.textContent = "☀ Light Mode";

            }
            else{

                localStorage.setItem("theme","light");
                themeBtn.textContent = "🌙 Dark Mode";

            }

        });

    }


    /* ===========================
       HAMBURGER MENU
    =========================== */

    const menuBtn = document.getElementById("menuBtn");
    const navMenu = document.getElementById("navMenu");

    if(menuBtn && navMenu){

        menuBtn.addEventListener("click", function(){

            navMenu.classList.toggle("show");

        });

    }

});
/* Notification Banner */

const closeNotification = document.getElementById("closeNotification");
const notification = document.getElementById("notification");

if(closeNotification && notification){

    closeNotification.addEventListener("click", function(){

        notification.style.display = "none";

    });

}