

    /* ===========================
       FAQ
    =========================== */

    const questions = document.querySelectorAll(".faq-question");

    questions.forEach(function (question) {

        question.addEventListener("click", function () {

            const answer = question.nextElementSibling;

            answer.classList.toggle("show");

            const symbol = question.querySelector("span");

            if (symbol) {
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

    if (openModal && closeModal && studentModal) {

        openModal.addEventListener("click", function () {
            studentModal.classList.add("show");
        });

        closeModal.addEventListener("click", function () {
            studentModal.classList.remove("show");
        });

        window.addEventListener("click", function (event) {

            if (event.target === studentModal) {
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

    if (slides.length > 0 && next && prev) {

        let current = 0;

        function showSlide(index) {

            slides.forEach(function (slide) {
                slide.classList.remove("active");
            });

            slides[index].classList.add("active");

        }

        showSlide(current);

        next.addEventListener("click", function () {

            current++;

            if (current >= slides.length) {
                current = 0;
            }

            showSlide(current);

        });

        prev.addEventListener("click", function () {

            current--;

            if (current < 0) {
                current = slides.length - 1;
            }

            showSlide(current);

        });

    }


    /* ===========================
       DARK MODE
    =========================== */
    document.addEventListener("DOMContentLoaded", function () {

    const themeBtn = document.getElementById("themeBtn");

    if (localStorage.getItem("theme") === "dark") {

        document.body.classList.add("dark-mode");

        if (themeBtn) {
            themeBtn.textContent = "☀ Light Mode";
        }

    }

    if (themeBtn) {

        themeBtn.addEventListener("click", function () {

            document.body.classList.toggle("dark-mode");

            if (document.body.classList.contains("dark-mode")) {

                localStorage.setItem("theme", "dark");
                themeBtn.textContent = "☀ Light Mode";

            } else {

                localStorage.setItem("theme", "light");
                themeBtn.textContent = "🌙 Dark Mode";

            }

        });

    }

    });
    /* ===========================
       HAMBURGER MENU
    =========================== */

    const menuBtn = document.getElementById("menuBtn");
    const navMenu = document.getElementById("navMenu");

    if (menuBtn && navMenu) {

        menuBtn.addEventListener("click", function () {
            navMenu.classList.toggle("show");
        });

    }


    /* ===========================
       NOTIFICATION
    =========================== */

    const closeNotification = document.getElementById("closeNotification");
    const notification = document.getElementById("notification");

    if (closeNotification && notification) {

        closeNotification.addEventListener("click", function () {
            notification.style.display = "none";
        });

    }


    /* ===========================
       PROFILE / REGISTRATION FORM
    =========================== */


const form = document.getElementById("profileForm");

if (form) {
    form.addEventListener("submit", (e) => {
        e.preventDefault();

        let isValid = true;

        // Get form fields
        const name = document.getElementById("name");
        const enrollment = document.getElementById("enrollment");
        const email = document.getElementById("email");
        const age = document.getElementById("age");
        const phone = document.getElementById("phone");
        const password = document.getElementById("password");
        const confirmPassword = document.getElementById("confirmPassword");
        const course = document.getElementById("course");
        const year = document.getElementById("year");
        const photo = document.getElementById("photo");
        const subject = document.getElementById("subject");
        const terms = document.getElementById("terms");

        // Error elements
        const nameError = document.getElementById("nameError");
        const enrollmentError = document.getElementById("enrollmentError");
        const emailError = document.getElementById("emailError");
        const ageError = document.getElementById("ageError");
        const phoneError = document.getElementById("phoneError");
        const passwordError = document.getElementById("passwordError");
        const confirmError = document.getElementById("confirmPasswordError");
        const courseError = document.getElementById("courseError");
        const yearError = document.getElementById("yearError");
        const photoError = document.getElementById("photoError");
        const genderError = document.getElementById("genderError");
        const subjectError = document.getElementById("subjectError");
        const termsError = document.getElementById("termsError");

        // Clear previous errors
        const errors = [
            nameError, enrollmentError, emailError, ageError,
            phoneError, passwordError, confirmError, courseError,
            yearError, photoError, genderError, subjectError, termsError
        ];

        errors.forEach(error => {
            if (error) {
                error.textContent = "";
                error.style.display = "none";
            }
        });

        // 1. Name validation
        if (name && !/^[A-Za-z ]{3,30}$/.test(name.value.trim())) {
            showError(nameError, "Enter a valid name (3–30 letters).");
            isValid = false;
        }

        // 2. Enrollment ID validation
        if (enrollment && !/^[0-9]{2}[A-Za-z]{2}[0-9]{3}$/.test(enrollment.value.trim())) {
            showError(enrollmentError, "Enter a valid Enrollment ID, e.g. 25CS092.");
            isValid = false;
        }

        // 3. Email validation
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
            showError(emailError, "Enter a valid email address.");
            isValid = false;
        }

        // 4. Age validation
        if (age && (age.value === "" || age.value < 16 || age.value > 35)) {
            showError(ageError, "Age must be between 16 and 35.");
            isValid = false;
        }

        // 5. Phone validation
        if (phone && !/^[6-9][0-9]{9}$/.test(phone.value.trim())) {
            showError(phoneError, "Enter a valid 10-digit mobile number.");
            isValid = false;
        }

        // 6. Password validation
        const passwordPattern =
            /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*]).{8,}$/;

        if (password && !passwordPattern.test(password.value)) {
            showError(
                passwordError,
                "Password needs 8 characters, uppercase, lowercase, a number and a special character."
            );
            isValid = false;
        }

        // 7. Confirm password validation
        if (confirmPassword && password &&
            confirmPassword.value !== password.value) {
            showError(confirmError, "Passwords do not match.");
            isValid = false;
        }

        // 8. Gender validation
        const gender = document.querySelector('input[name="gender"]:checked');

        if (!gender) {
            showError(genderError, "Please select your gender.");
            isValid = false;
        }

        // 9. Photo validation
        if (photo && photo.files.length === 0) {
            showError(photoError, "Please upload your student photo.");
            isValid = false;
        }

        // 10. Course validation
        if (course && course.value === "") {
            showError(courseError, "Please select your course.");
            isValid = false;
        }

        // 11. Year validation
        if (year && year.value === "") {
            showError(yearError, "Please select your year.");
            isValid = false;
        }

        // 12. Subject validation
        if (subject && subject.value === "") {
            showError(subjectError, "Please select a subject.");
            isValid = false;
        }

       

        // 14. Terms validation
        if (terms && !terms.checked) {
            showError(termsError, "Please accept the Terms and Conditions.");
            isValid = false;
        }

        // Display result
        if (isValid) {
            alert("Registration completed successfully!");
            form.reset();
        }
    });
}

// Display error message
function showError(element, message) {
    if (element) {
        element.textContent = message;
        element.style.display = "block";
    }
}