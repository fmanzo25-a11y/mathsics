document.addEventListener('DOMContentLoaded', () => {
    const likeButton = document.querySelector('.like-button');
    const dislikeButton = document.querySelector('.dislike-button');
    const likeCount = document.querySelector('.like-count');
    const dislikeCount = document.querySelector('.dislike-count');

    let likes = 0;
    let dislikes = 0;
    let userVote = null; // 'like', 'dislike', or null

    function updateCounts() {
        likeCount.textContent = likes;
        dislikeCount.textContent = dislikes;
    }

    likeButton.addEventListener('click', () => {
        if (userVote === 'like') {
            likes--;
            userVote = null;
            likeButton.classList.remove('active');
        } else {
            if (userVote === 'dislike') {
                dislikes--;
                dislikeButton.classList.remove('active');
            }
            likes++;
            userVote = 'like';
            likeButton.classList.add('active');
        }
        updateCounts();
    });

    dislikeButton.addEventListener('click', () => {
        if (userVote === 'dislike') {
            dislikes--;
            userVote = null;
            dislikeButton.classList.remove('active');
        } else {
            if (userVote === 'like') {
                likes--;
                likeButton.classList.remove('active');
            }
            dislikes++;
            userVote = 'dislike';
            dislikeButton.classList.add('active');
        }
        updateCounts();
    });

    updateCounts(); // Initialize counts
});