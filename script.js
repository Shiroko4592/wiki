// 로컬스토리지에서 페이지 데이터 불러오기
let pages = JSON.parse(localStorage.getItem('pages') || '{}');

// 현재 보고 있는 페이지
let currentPage = 'Main Page';

// 초기 페이지가 없으면 Main Page 추가
if(!pages[currentPage]) pages[currentPage] = "Static Wiki에 오신 것을 환영합니다!";

const pageTitle = document.getElementById('page-title');
const pageContent = document.getElementById('page-content');
const editBtn = document.getElementById('edit-btn');
const newBtn = document.getElementById('new-btn');
const editor = document.getElementById('editor');
const editContent = document.getElementById('edit-content');
const saveBtn = document.getElementById('save-btn');
const cancelBtn = document.getElementById('cancel-btn');
const pageList = document.getElementById('page-list');

function renderPage() {
    pageTitle.textContent = currentPage;
    pageContent.textContent = pages[currentPage] || "";
    renderPageList();
}

function renderPageList() {
    pageList.innerHTML = '';
    for(let p in pages) {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = "#";
        a.textContent = p;
        a.addEventListener('click', ()=> {
            currentPage = p;
            renderPage();
        });
        li.appendChild(a);
        pageList.appendChild(li);
    }
}

editBtn.addEventListener('click', ()=> {
    editor.style.display = 'block';
    pageContent.style.display = 'none';
    editContent.value = pages[currentPage];
});

cancelBtn.addEventListener('click', ()=> {
    editor.style.display = 'none';
    pageContent.style.display = 'block';
});

saveBtn.addEventListener('click', ()=> {
    pages[currentPage] = editContent.value;
    localStorage.setItem('pages', JSON.stringify(pages));
    editor.style.display = 'none';
    pageContent.style.display = 'block';
    renderPage();
});

newBtn.addEventListener('click', ()=> {
    const name = prompt("새 페이지 이름:");
    if(name) {
        pages[name] = "";
        currentPage = name;
        localStorage.setItem('pages', JSON.stringify(pages));
        renderPage();
    }
});

// 초기 렌더
renderPage();
