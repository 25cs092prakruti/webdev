
const form = document.getElementById("profileForm");
const photoInput = document.getElementById("photo");
const photoPreview = document.getElementById("photoPreview");

function showError(id, shouldShow) {
    const error = document.getElementById(id);

    if (error) {
        error.classList.toggle("show", shouldShow);
    }
}

// Hide validation errors when the page first loads.
const errorIds = [
    "nameError", "enrollmentError", "emailError", "ageError",
    "genderError", "photoError", "courseError", "yearError",
    "passwordError", "confirmPasswordError", "phoneError",
    "subjectError", "messageError", "termsError"
];

errorIds.forEach(id => showError(id, false));

// Student photo preview.
if (photoInput && photoPreview) {
    photoInput.addEventListener("change", () => {
        const file = photoInput.files[0];

        if (file && file.type.startsWith("image/")) {
            photoPreview.src = URL.createObjectURL(file);
            photoPreview.hidden = false;
        } else {
            photoPreview.removeAttribute("src");
            photoPreview.hidden = true;
        }
    });
}

// Custom form validation.
if (form) {
    form.addEventListener("submit", event => {
        event.preventDefault();

        const getValue = id =>
            document.getElementById(id)?.value.trim() ?? "";

        const name = getValue("fullName");
        const enrollment = getValue("enrollmentId");
        const email = getValue("email");
        const ageText = getValue("age");
        const age = Number(ageText);
        const password = document.getElementById("password")?.value ?? "";
        const confirm = getValue("confirmPassword");
        const phone = getValue("phone");
        const message = getValue("message");

        const checks = {
            nameError: !/^[A-Za-z ]{3,30}$/.test(name),

            enrollmentError:
                !/^\d{2}[A-Za-z]{2}\d{3,}$/.test(enrollment),

            emailError:
                !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email),

            ageError:
                ageText === "" || !Number.isInteger(age) ||
                age < 16 || age > 35,

            genderError:
                !form.querySelector('input[name="gender"]:checked'),

            photoError:
                !photoInput?.files.length ||
                !photoInput.files[0].type.startsWith("image/"),

            courseError: !getValue("course"),
            yearError: !getValue("year"),

            passwordError: !(
                password.length >= 8 &&
                /[A-Z]/.test(password) &&
                /[a-z]/.test(password) &&
                /\d/.test(password) &&
                /[^A-Za-z0-9]/.test(password)
            ),

            confirmPasswordError:
                !confirm || confirm !== password,

            phoneError: !/^[6-9]\d{9}$/.test(phone),

            subjectError: !getValue("subject"),
            messageError: !message,

            termsError:
                !document.getElementById("terms")?.checked
        };

        let valid = true;

        Object.entries(checks).forEach(([id, hasError]) => {
            showError(id, hasError);

            if (hasError) {
                valid = false;
            }
        });

        const formMessage = document.getElementById("formMessage");

        if (formMessage) {
            formMessage.textContent = valid
                ? "All fields are valid! This demo does not save data permanently."
                : "Please correct the errors shown above.";

            formMessage.className = valid
                ? "message"
                : "message error";
        }
    });
}

import { initDirectory } from "./data-directory.js";

// Load Students JSON
initDirectory({
    key: "students",
    file: "data/students.json",
    titleField: "name",
    detailFields: [
        { key: "department", label: "Department" },
        { key: "gpa", label: "GPA" },
        { key: "active", label: "Status" }
    ],
    searchFields: ["name", "department"],
    filterField: "department",
    pageSize: 5
});

// Load Events JSON
initDirectory({
    key: "events",
    file: "data/events.json",
    titleField: "title",
    detailFields: [
        { key: "category", label: "Category" },
        { key: "date", label: "Date" },
        { key: "venue", label: "Venue" }
    ],
    searchFields: ["title", "category", "venue"],
    filterField: "category",
    pageSize: 5
});

// Load FAQs JSON
initDirectory({
    key: "faqs",
    file: "data/faqs.json",
    titleField: "question",
    detailFields: [
        { key: "answer", label: "Answer" },
        { key: "category", label: "Category" }
    ],
    searchFields: ["question", "answer", "category"],
    filterField: "category",
    pageSize: 5
});

// Keep your existing profile form code below this line
