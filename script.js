document.getElementById('mwInstallForm')?.addEventListener('submit', function(){
    const result = document.getElementById('mwResult');
    if(result) result.innerText = "설치 진행 중...";
});
