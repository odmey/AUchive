window.addEventListener("DOMContentLoaded", function () {
    const storyData = JSON.parse(localStorage.getItem("storyData"));
    const chapterData = JSON.parse(localStorage.getItem("chapterData"));
    const bubbleChatData = localStorage.getItem("bubbleChatData");

    if (storyData) {
        document.getElementById("storyTitle").textContent = storyData.title;
    }

    if (chapterData) {
        document.getElementById("storyParagraph").innerHTML =
            "<h2>" + chapterData.chapterTitle + "</h2><p>" +
            chapterData.paragraph + "</p>";
    }

    if (bubbleChatData) {
        document.getElementById("chatStory").innerHTML = bubbleChatData;
    }
});
window.onload = function () { 
    const savedParagraph = localStorage.getItem("storyParagraph"); 
    const savedBubble = localStorage.getItem("bubbleChatData"); 
    const paragraphBox = document.getElementById("storyParagraph"); 
    const bubbleBox = document.getElementById("bubbleContainer"); 
    // tampilkan narasi 
    if (savedParagraph) { paragraphBox.innerHTML = ` <div class="story-narration"> ${savedParagraph} </div> `; 
    } 
    if (savedBubble) { bubbleBox.innerHTML = savedBubble; } 
};
