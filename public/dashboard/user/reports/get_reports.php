<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Outage Reports</title>

<style>
body{
    font-family: Arial;
    padding: 20px;
    background: #f4f4f4;
}

.card{
    background: #fff;
    border-radius: 10px;
    padding: 12px;
    margin-bottom: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

h2{
    margin-bottom: 15px;
}

.pagination{
    display: flex;
    gap: 5px;
    margin-top: 20px;
    flex-wrap: wrap;
}

.page-btn{
    padding: 8px 12px;
    border: none;
    background: #ddd;
    cursor: pointer;
    border-radius: 5px;
}

.page-btn.active{
    background: #333;
    color: #fff;
}

.page-btn:hover{
    background: #999;
    color: #fff;
}

#loading{
    padding: 10px;
}
</style>
</head>

<body>

<h2>Outage Reports</h2>

<div id="list"></div>
<div class="pagination" id="pagination"></div>

<script>

let allData = [];
let currentPage = 1;
const perPage = 5;

/* ===============================
   SAFE TEXT HELPER (XSS PROTECTION)
================================= */
function escapeHTML(str){
    return String(str ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

/* ===============================
   FETCH DATA (JWT ENABLED)
================================= */
async function loadData(){

    const list = document.getElementById("list");
    list.innerHTML = "<p id='loading'>Loading...</p>";

    try {

        const res = await fetch(
            "http://localhost/crowdsourcedapi/api/outage_report/get.php",
            {
                method: "GET",
                credentials: "include" // JWT COOKIE
            }
        );

        const result = await res.json();

        if(!result || result.success === false){
            list.innerHTML = "<p>No data found</p>";
            return;
        }

        allData = Array.isArray(result.data) ? result.data : [];

        currentPage = 1;
        renderPage();
        renderPagination();

    } catch(err){
        console.error(err);
        list.innerHTML = "<p>Error loading data</p>";
    }
}

/* ===============================
   RENDER PAGE (OPTIMIZED)
================================= */
function renderPage(){

    const list = document.getElementById("list");
    list.innerHTML = "";

    const start = (currentPage - 1) * perPage;
    const pageData = allData.slice(start, start + perPage);

    if(pageData.length === 0){
        list.innerHTML = "<p>No records</p>";
        return;
    }

    const fragment = document.createDocumentFragment();

    pageData.forEach(item => {

        const div = document.createElement("div");
        div.className = "card";

        div.innerHTML = `
            <h3>${escapeHTML(item.location_name)}</h3>
            <p>${escapeHTML(item.description || "No description")}</p>
            <small>
                Category: ${escapeHTML(item.category)} |
                Severity: ${escapeHTML(item.severity)} |
                Status: ${escapeHTML(item.status)}
            </small>
        `;

        fragment.appendChild(div);
    });

    list.appendChild(fragment);
}

/* ===============================
   PAGINATION (IMPROVED UX)
================================= */
function renderPagination(){

    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";

    const totalPages = Math.ceil(allData.length / perPage);

    if(totalPages <= 1) return;

    /* PREV */
    const prev = document.createElement("button");
    prev.textContent = "Prev";
    prev.className = "page-btn";
    prev.disabled = currentPage === 1;

    prev.onclick = () => {
        if(currentPage > 1){
            currentPage--;
            renderPage();
            renderPagination();
        }
    };

    pagination.appendChild(prev);

    /* PAGES */
    for(let i = 1; i <= totalPages; i++){

        const btn = document.createElement("button");
        btn.textContent = i;
        btn.className = "page-btn";

        if(i === currentPage){
            btn.classList.add("active");
        }

        btn.onclick = () => {
            currentPage = i;
            renderPage();
            renderPagination();
        };

        pagination.appendChild(btn);
    }

    /* NEXT */
    const next = document.createElement("button");
    next.textContent = "Next";
    next.className = "page-btn";
    next.disabled = currentPage === totalPages;

    next.onclick = () => {
        if(currentPage < totalPages){
            currentPage++;
            renderPage();
            renderPagination();
        }
    };

    pagination.appendChild(next);
}

/* ===============================
   INIT
================================= */
loadData();

</script>

</body>
</html>