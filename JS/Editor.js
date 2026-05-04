document.getElementById("publishBtn").addEventListener("click", function () {
    const chapterData = {
        chapterTitle: document.querySelector(".editor-title").value,
        paragraph: document.querySelector(".editor-paragraph").value
    };

    localStorage.setItem("chapterData", JSON.stringify(chapterData));

    window.location.href = "ReadingPage.html";
});