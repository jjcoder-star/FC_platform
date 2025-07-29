document.addEventListener("DOMContentLoaded", function () {
  // LIKE BUTTON FUNCTIONALITY
  document.querySelectorAll(".like-btn").forEach(function (likeBtn) {
    likeBtn.addEventListener("click", function () {
      const postId = this.getAttribute("data-post-id");

      if (this.classList.contains("liked")) {
        this.classList.remove("liked");
        this.style.color = "gray";
        // Optional: AJAX call to remove like
      } else {
        this.classList.add("liked");
        this.style.color = "red";
        // Optional: AJAX call to add like
      }
    });
  });

  // COMMENT TOGGLE & LOAD COMMENTS
  document.querySelectorAll(".comment-toggle").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const postId = btn.getAttribute("data-post-id");
      const container = document.getElementById(`comments-${postId}`);

      if (container.style.display === "none" || container.style.display === "") {
        container.style.display = "block";

        // Fetch comments
        fetch(`comments.php?post_id=${postId}`)
          .then(res => res.text())
          .then(data => {
            document.getElementById(`comment-list-${postId}`).innerHTML = data;
          });
      } else {
        container.style.display = "none";
      }
    });
  });

  // SUBMIT COMMENT
  document.querySelectorAll(".comment-form").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();

      const postId = form.getAttribute("data-post-id");
      const input = form.querySelector("input[name='comment_text']");
      const text = input.value.trim();

      if (text !== "") {
        const formData = new FormData();
        formData.append("post_id", postId);
        formData.append("comment_text", text);

        fetch("submit_comment.php", {
          method: "POST",
          body: formData
        })
        .then(res => res.text())
        .then(response => {
          if (response === "success") {
            input.value = "";

            // Refresh comment list
            fetch(`comments.php?post_id=${postId}`)
              .then(res => res.text())
              .then(data => {
                document.getElementById(`comment-list-${postId}`).innerHTML = data;
              });
          }
        });
      }
    });
  });

  // SHARE BUTTON POPUP
  document.querySelectorAll(".share-btn").forEach(function (btn) {
    btn.addEventListener("click", function () {
      const postId = this.getAttribute("data-post-id");
      const shareOptions = document.createElement("div");
      shareOptions.classList.add("share-popup");

      shareOptions.innerHTML = `
        <p><strong>Share this post:</strong></p>
        <button onclick="copyLink(${postId})">📋 Copy Link</button>
        <button onclick="alert('Feature coming: Share with in-app friends')">💬 Share to Friend</button>
        <button onclick="alert('Coming soon: Share to Instagram')">📷 Instagram</button>
        <button onclick="alert('Coming soon: Share to WhatsApp')">📱 WhatsApp</button>
      `;

      document.body.appendChild(shareOptions);

      // Auto-close after 5 seconds
      setTimeout(() => {
        shareOptions.remove();
      }, 5000);
    });
  });
});

// COPY LINK FUNCTION
function copyLink(postId) {
  const dummy = document.createElement("input");
  const url = `${window.location.href}?post_id=${postId}`;
  document.body.appendChild(dummy);
  dummy.value = url;
  dummy.select();
  document.execCommand("copy");
  document.body.removeChild(dummy);
  alert("🔗 Post link copied!");
}
