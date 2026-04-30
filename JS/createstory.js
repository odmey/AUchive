// pastikan semua elemen sudah kebaca
document.addEventListener("DOMContentLoaded", function () {

    // ==============================
    // PREVIEW COVER IMAGE
    // ==============================
    const coverInput = document.getElementById("cover");
    const preview = document.getElementById("previewCover");

    coverInput.addEventListener("change", function (event) {
        const file = event.target.files[0];

        if (file) {

            // validasi: harus gambar
            if (!file.type.startsWith("image/")) {
                alert("File harus berupa gambar!");
                coverInput.value = "";
                preview.style.display = "none";
                return;
            }

            const reader = new FileReader();

            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = "block";
            };

            reader.readAsDataURL(file);
        }
    });

    // ==============================
    // KIRIM DATA KE EDITOR
    // ==============================
    document.getElementById("nextBtn").addEventListener("click", function () {

        const judul = document.getElementById("judul").value.trim();
        const deskripsi = document.getElementById("deskripsi").value.trim();
        const genre = document.getElementById("genre").value;
        const tagar = document.getElementById("tagar").value.trim();

        // validasi sederhana
        if (judul === "" || deskripsi === "") {
            alert("Judul dan Deskripsi wajib diisi!");
            return;
        }

        // simpan ke localStorage
        localStorage.setItem("judul", judul);
        localStorage.setItem("deskripsi", deskripsi);
        localStorage.setItem("genre", genre);
        localStorage.setItem("tagar", tagar);

        // (opsional) simpan cover juga (base64)
        if (preview.src) {
            localStorage.setItem("cover", preview.src);
        }

        // pindah halaman
        window.location.href = "Editor.html";
    });

});