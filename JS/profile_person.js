const followBtn = document.getElementById("followBtn");

if (followBtn) {

    let followed = false;

    followBtn.addEventListener("click", () => {

        followed = !followed;

        if (followed) {

            followBtn.textContent = "Following";

            followBtn.style.background = "transparent";
            followBtn.style.color = "#FFF44F";
            followBtn.style.border = "1px solid #FFF44F";

        } else {

            followBtn.textContent = "Follow";

            followBtn.style.background = "#FFF44F";
            followBtn.style.color = "black";
            followBtn.style.border = "none";
        }
    });
}

/* FOLLOWERS / FOLLOWING MODAL */

const followersBtn = document.getElementById("followersBtn");
const followingBtn = document.getElementById("followingBtn");

const followModal = document.getElementById("followModal");
const closeModal = document.getElementById("closeModal");

const modalTitle = document.getElementById("modalTitle");
const userList = document.getElementById("userList");

/* DUMMY DATA */

const followers = [

    {
        name: "Bryant",
        username: "@Bryant24",
        image: "Pic/profileicon.jpg"
    },

    {
        name: "Luna",
        username: "@lunaria",
        image: "Pic/profileicon.jpg"
    },

    {
        name: "Rei",
        username: "@reikaze",
        image: "Pic/profileicon.jpg"
    }
];

const following = [

    {
        name: "Mika",
        username: "@mikasaa",
        image: "Pic/profileicon.jpg"
    },

    {
        name: "Aether",
        username: "@aetherlight",
        image: "Pic/profileicon.jpg"
    }
];

/* OPEN MODAL */

function openModal(title, users) {

    modalTitle.textContent = title;

    userList.innerHTML = "";

    users.forEach(user => {

        userList.innerHTML += `

            <div class="user-item">

                <img src="${user.image}">

                <div class="user-info">
                    <h4>${user.name}</h4>
                    <p>${user.username}</p>
                </div>

            </div>

        `;
    });

    followModal.classList.add("show");
}

/* BUTTON EVENTS */

followersBtn.addEventListener("click", () => {
    openModal("Followers", followers);
});

followingBtn.addEventListener("click", () => {
    openModal("Following", following);
});

/* CLOSE MODAL */

closeModal.addEventListener("click", () => {
    followModal.classList.remove("show");
});

/* CLOSE OUTSIDE */

followModal.addEventListener("click", (e) => {

    if (e.target === followModal) {
        followModal.classList.remove("show");
    }
});