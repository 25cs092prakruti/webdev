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
const form = document.getElementById("profileForm");

if (form) {

form.addEventListener("submit", function(e){

e.preventDefault();
document.querySelectorAll(".error-input").forEach(function(field){
    field.classList.remove("error-input");
});

let valid = true;

document.getElementById("nameError").innerHTML = "";
document.getElementById("enrollmentError").innerHTML = "";
document.getElementById("emailError").innerHTML = "";
document.getElementById("phoneError").innerHTML = "";
document.getElementById("ageError").innerHTML = "";
document.getElementById("genderError").innerHTML = "";
document.getElementById("photoError").innerHTML = "";
document.getElementById("passwordError").innerHTML = "";
document.getElementById("confirmPasswordError").innerHTML = "";
document.getElementById("subjectError").innerHTML = "";
document.getElementById("messageError").innerHTML = "";

let name = document.getElementById("name").value.trim();
let enrollment = document.getElementById("enrollment").value.trim();
let email = document.getElementById("email").value.trim();
let phone = document.getElementById("phone").value.trim();
let age = document.getElementById("age").value;
let photo = document.getElementById("photo").value;
let password = document.getElementById("password").value;
let confirmPassword = document.getElementById("confirmPassword").value;
let subject = document.getElementById("subject").value;
let message = document.getElementById("message").value.trim();

let gender = document.querySelector('input[name="gender"]:checked');

let nameRegex = /^[A-Za-z ]{3,30}$/;
let enrollmentRegex = /^[0-9]{2}[A-Za-z]{2}[0-9]{3}$/;
let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
let phoneRegex = /^[6-9][0-9]{9}$/;
let passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/;

if(!nameRegex.test(name)){
document.getElementById("nameError").innerHTML="Enter a valid name.";
document.getElementById("name").classList.add("error-input");
valid=false;
}

if(!enrollmentRegex.test(enrollment)){
document.getElementById("enrollmentError").innerHTML="Example: 25CS092";
document.getElementById("enrollment").classList.add("error-input");
valid=false;
}

if(!emailRegex.test(email)){
document.getElementById("emailError").innerHTML="Enter a valid email.";
document.getElementById("email").classList.add("error-input");
valid=false;
}

if(!phoneRegex.test(phone)){
document.getElementById("phoneError").innerHTML="Enter a valid mobile number.";
document.getElementById("phone").classList.add("error-input");
valid=false;
}

if(age < 16 || age > 35){
document.getElementById("ageError").innerHTML="Age must be between 16 and 35.";
document.getElementById("age").classList.add("error-input");
valid=false;
}

if(!gender){
document.getElementById("genderError").innerHTML="Select your gender.";
valid=false;
}

if(photo==""){
document.getElementById("photoError").innerHTML="Upload your photo.";
document.getElementById("photo").classList.add("error-input");
valid=false;
}

if(!passwordRegex.test(password)){
document.getElementById("passwordError").innerHTML="Password must contain uppercase, lowercase, number and special character.";
document.getElementById("password").classList.add("error-input");
valid=false;
}

if(password!=confirmPassword){
document.getElementById("confirmPasswordError").innerHTML="Passwords do not match.";
document.getElementById("confirmPassword").classList.add("error-input");
valid=false;
}

if(subject=="Select Subject"){
document.getElementById("subjectError").innerHTML="Select a subject.";
document.getElementById("subject").classList.add("error-input");
valid=false;
}

if(message.length<10){
document.getElementById("messageError").innerHTML="Message must contain at least 10 characters.";
valid=false;
}

if(valid){
alert("Profile Updated Successfully!");
form.reset();
document.getElementById("passwordStrength").innerHTML="";
}

});



const passwordBox = document.getElementById("password");

if(passwordBox){

passwordBox.addEventListener("keyup",function(){

let value=passwordBox.value;
let strength=document.getElementById("passwordStrength");

if(value.length<6){

strength.innerHTML="Weak";
strength.style.color="red";

}
else if(
value.match(/[A-Z]/) &&
value.match(/[a-z]/) &&
value.match(/[0-9]/) &&
value.match(/[!@#$%^&*]/)
){

strength.innerHTML="Strong";
strength.style.color="green";

}
else{

strength.innerHTML="Medium";
strength.style.color="orange";

}

});

}
document.querySelectorAll("input,select,textarea").forEach(function(field){

field.addEventListener("input",function(){

this.classList.remove("error-input");

let error=document.getElementById(this.id+"Error");

if(error){

error.innerHTML="";

}

});

});
}