const avatars={me:null,contact:null};

const THEMES={
  wa:{status:'online',placeholder:'Type a message',
      sendSvg:'<svg viewBox="0 0 24 24"><path d="M12 1a4 4 0 014 4v6a4 4 0 01-8 0V5a4 4 0 014-4zm-1 17.93V21h2v-2.07A8 8 0 0020 11h-2a6 6 0 01-12 0H4a8 8 0 007 7.93z"/></svg>',
      defColor:'#005c4b'},
  im:{status:'',sendSvg:'<svg viewBox="0 0 24 24"><path d="M5 12h14M12 5l7 7-7 7"/></svg>',
      defColor:'#007aff'},
};

function setTheme(t,card){
  document.body.classList.remove('theme-wa','theme-ig','theme-im');
  document.body.classList.add('theme-'+t);
  document.querySelectorAll('.theme-card').forEach(c=>c.classList.remove('active'));
  card.classList.add('active');
  const cfg=THEMES[t];
  document.getElementById('previewStatus').textContent=cfg.status;
  ['hic1','hic2','hic3'].forEach((id,i)=>document.getElementById(id).textContent=cfg.icons[i]||'');
  document.getElementById('ibarField').placeholder=cfg.placeholder;
  document.getElementById('ibarSend').innerHTML=cfg.sendSvg;
  document.getElementById('bubbleColor').value=cfg.defColor;
}

function loadAvatar(input,who){
  const f=input.files[0];if(!f)return;
  const r=new FileReader();
  r.onload=e=>{avatars[who]=e.target.result;
    if(who==='contact'){document.getElementById('previewAvatar').innerHTML=`<img src="${e.target.result}" alt="">`;}
  };r.readAsDataURL(f);
}

function loadBg(input){
  const f=input.files[0];if(!f)return;
  const r=new FileReader();
  r.onload=e=>{const a=document.getElementById('chatArea');
    a.style.backgroundImage=`url(${e.target.result})`;
    a.style.backgroundSize='cover';a.style.backgroundPosition='center';
  };r.readAsDataURL(f);
}

function formatTime(v){
  if(!v)return'';const[h,m]=v.split(':');
  const hr=parseInt(h),ap=hr>=12?'PM':'AM',h12=hr%12||12;
  return`${h12}:${m} ${ap}`;
}

function addBubble(){
  const msg=document.getElementById('message').value.trim();
  const tv=document.getElementById('time').value;
  const color=document.getElementById('bubbleColor').value;
  const side=document.querySelector('input[name="side"]:checked').value;
  const imgFile=document.getElementById('imageUpload').files[0];
  const ts=formatTime(tv);

  if(!msg&&!imgFile){
    const el=document.getElementById('message');
    el.classList.add('field-input-error');el.focus();
    setTimeout(()=>el.classList.remove('field-input-error'),800);return;
  }

  const row=document.createElement('div');
  row.className=`bubble-row ${side}`;
  const av=document.createElement('div');av.className='row-avatar';
  const avSrc=side==='left'?avatars.contact:avatars.me;
  av.innerHTML=avSrc?`<img src="${avSrc}" alt="">`:(side==='left'?'👤':'🙂');

  if(imgFile){
    const r=new FileReader();
    r.onload=e=>{
      const w=document.createElement('div');w.className='bubble-img-wrap';
      w.innerHTML=`<img src="${e.target.result}"><span class="bubble-time">${ts}</span>`;
      row.appendChild(av);row.appendChild(w);
      document.getElementById('chatArea').appendChild(row);scrollBottom();
    };r.readAsDataURL(imgFile);
    document.getElementById('imageUpload').value='';
  }else{
    const b=document.createElement('div');b.className='bubble';
    b.style.background=color;
    b.innerHTML=`${escHtml(msg)}<div class="bubble-meta"><span class="bubble-time">${ts}</span></div>`;
    row.appendChild(av);row.appendChild(b);
    document.getElementById('chatArea').appendChild(row);scrollBottom();
  }
  document.getElementById('message').value='';
}

function clearChat(){document.getElementById('chatArea').innerHTML='<div class="date-chip"><span>Today</span></div>';}

function saveStory() {
    const chatArea = document.getElementById("chatArea");

    localStorage.setItem("bubbleChatData", chatArea.innerHTML);

    const btn = document.querySelector(".btn-save");
    btn.textContent = "✓ SAVED!";

    setTimeout(() => {
        window.location.href = "Editor.html";
    }, 1000);
}

function scrollBottom(){const a=document.getElementById('chatArea');a.scrollTop=a.scrollHeight;}

function escHtml(s){return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');}
