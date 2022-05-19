(function() {
  const isTest = /wc-test|localhost/.test(location.href);
  const baseUrl = isTest
    ? "https://gateway.apaylater.net"
    : "https://gateway.apaylater.com";
  const introBaseUrl = baseUrl + '/plugins/intro';

  var head = document.getElementsByTagName('head')[0];
  var link = document.createElement('link');
  link.rel = 'stylesheet';
  link.type = 'text/css';
  link.href = introBaseUrl + '/index.css';
  link.media = 'all';
  head.appendChild(link);

  document.addEventListener('click', function(e) {
    var ele = e.target;
    if (ele.className && typeof ele.className === 'string' && ele.className.includes('atome-icon')) {
      e.preventDefault();
      e.stopPropagation();
      if (document.querySelector('.atome-mask')) {
        document.querySelector('.atome-mask').style.display = '';
      } else {
        appendTemplateOnce();
      }
    } else if (ele.className && typeof ele.className === 'string' && ele.className.includes('atome-mask')) {
      document.querySelector('.atome-mask').style.display = 'none';
    }
  });

  function appendTemplateOnce() {
    const template = `
        <div class="atome-main">
            <img class="sub left" src="${introBaseUrl}/cover.png" />
            <div class="sub right">
                <img onclick="atomeWidget.hideAtome()" class="close" src="${introBaseUrl}/close.png" />
                <img class="atome-intro-logo" src="${baseUrl}/plugins/logo.png" />
                <div class="atome-love-it">Love it. Own it. Pay later.</div>
                <div class="atome-no-wait">Why wait? Shop today, 0% interest over 3 instalments.</div>
                <div class="desc first">
                    <img src="${introBaseUrl}/icon1.png" />
                <div class="text">0% interest<br />100% transparent</div>
            </div>
            <div class="desc">
                <img src="${introBaseUrl}/icon2.png" />
                <div class="text">Split bill to 3 instalments<br />Hassle-free experience</div>
            </div>
            <div class="desc">
                <img src="${introBaseUrl}/icon3.png" />
                <div class="text">Hundreds of merchants<br />Vast variety</div>
            </div>
            <div onclick="atomeWidget.gotoWeb()" class="how-it-works">
                How it works?
            </div>
            </div>
        </div>`;

    var insertHtml = document.createElement('div');
    insertHtml.setAttribute('class', 'atome-mask');
    insertHtml.style.display = 'none';
    insertHtml.innerHTML = template;
    document.body.insertBefore(insertHtml, document.body.firstChild);
    setTimeout(function() {
      insertHtml.style.display = '';
    }, 300);
  }

  const addToAtomeWidget = {
    hideAtome,
    gotoWeb,
  };

  if (window.atomeWidget) {
    atomeWidget = { ...atomeWidget, ...addToAtomeWidget };
  } else {
    atomeWidget = addToAtomeWidget;
  }

  function hideAtome() {
    document.querySelector('.atome-mask').style.display = 'none';
  }

  function gotoWeb() {
    window.open('https://www.atome.sg');
  }
})();