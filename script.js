let pages = JSON.parse(localStorage.getItem('pages') || '{}');
let currentPage = 'Main Page';
if(!pages[currentPage]) pages[currentPage] = `[목차]\n= 첫번째 문단 =\n내용 입력`;

const pageTitle = document.getElementById('page-title');
const pageContent = document.getElementById('page-content');
const editBtn = document.getElementById('edit-btn');
const newBtn = document.getElementById('new-btn');
const editor = document.getElementById('editor');
const editContent = document.getElementById('edit-content');
const saveBtn = document.getElementById('save-btn');
const cancelBtn = document.getElementById('cancel-btn');
const pageList = document.getElementById('page-list');

let footnotes = [];
let footnoteId = 1;

function parseWiki(text) {
    footnotes = [];
    footnoteId = 1;

    // 문단 제목
    text = text.replace(/===== (.*?) =====/g, '<h5>$1</h5>')
               .replace(/==== (.*?) ====/g, '<h4>$1</h4>')
               .replace(/=== (.*?) ===/g, '<h3>$1</h3>')
               .replace(/== (.*?) ==/g, '<h2>$1</h2>')
               .replace(/= (.*?) =/g, '<h1>$1</h1>');

    // 취소선
    text = text.replace(/~~(.*?)~~/g, '<s>$1</s>');
    // 작은 글씨
    text = text.replace(/-(.*?)-/g, '<small>$1</small>');
    // 큰 글씨
    text = text.replace(/--(.*?)--/g, '<span style="font-size:1.5em;">$1</span>');
    // 기울임
    text = text.replace(/---(.*?)---/g, '<i>$1</i>');

    // 루비 문자 [[아하]]
    text = text.replace(/\[\[(.)\s*(.)\]\]/g, '<span class="ruby">$1<rt>$2</rt></span>');

    // 각주 [(글자)(각주글씨)]
    text = text.replace(/\[\((.*?)\)\((.*?)\)\]/g, (match, char, note)=>{
        let id = footnoteId++;
        footnotes.push({id,note});
        return `<span class="footnote" onclick="alert('${note}')">${char}<sup>${id}</sup></span>`;
    });

    // [[분류:...]]
    let categories = [];
    text = text.replace(/\[\[분류:(.*?)\]\]/g, (match, cat)=>{
        categories.push(cat);
        return '';
    });

    // [[htp://yt.kBedTDfk4vk]] → 유튜브
    text = text.replace(/\[\[htp:\/\/yt\.(.*?)\]\]/g, (match, id)=>{
        return `<iframe width="560" height="315" src="https://www.youtube.com/embed/${id}" frameborder="0" allowfullscreen></iframe>`;
    });

    // [목차]
    text = text.replace(/\[목차\]/g, () => {
        let toc = '';
        text.replace(/<h[1-5]>(.*?)<\/h[1-5]>/g, (m,title)=>{
            toc += `<li>${title}</li>`;
        });
        return `<ul>${toc}</ul>`;
    });

    if(categories.length>0){
        text += '<div><b>분류:</b> ' + categories.join(', ') + '</div>';
    }

    return text;
}

function renderPage() {
    pageTitle.textContent = currentPage;
    pageContent.innerHTML = parseWiki(pages[currentPage]);
    renderPageList();
}

function renderPageList() {
    pageList.innerHTML = '';
    for(let p in pages) {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.href = "#";
        a.textContent = p;
        a.addEventListener('click', ()=>{
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

saveBtn.addEventListener('click', ()=>{
    pages[currentPage] = editContent.value;
    localStorage.setItem('pages', JSON.stringify(pages));
    editor.style.display = 'none';
    pageContent.style.display = 'block';
    renderPage();
});

newBtn.addEventListener('click', ()=>{
    const name = prompt("새 페이지 이름:");
    if(name){
        pages[name] = "";
        currentPage = name;
        localStorage.setItem('pages', JSON.stringify(pages));
        renderPage();
    }
});

renderPage();
