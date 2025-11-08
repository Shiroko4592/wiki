// 필요 시 설치 진행 중 로딩 표시 등 추가 가능
document.getElementById('mwInstallForm')?.addEventListener('submit', function(){
    const result = document.getElementById('mwResult');
    if(result) result.innerText = "설치 진행 중...";
});
