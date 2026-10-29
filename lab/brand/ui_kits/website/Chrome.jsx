const IMG = {"logo":"../../assets/logo-maroon.png","hero":"https://emilydiazslp.com/wp-content/uploads/2026/04/emily-no-bg-white.webp","ipad":"https://emilydiazslp.com/wp-content/uploads/2023/05/ipad-mock-1024x1024.png","pro":"https://emilydiazslp.com/wp-content/uploads/2023/09/emily-professional-scaled.jpg","aboutEmily":"https://emilydiazslp.com/wp-content/uploads/2026/04/about-pg-emily.webp","edited":"https://emilydiazslp.com/wp-content/uploads/2025/03/emily-edited.png","flourish":"https://emilydiazslp.com/wp-content/uploads/2023/05/Asset-4-1.png","hub":"https://emilydiazslp.com/wp-content/uploads/2026/04/emily-no-bg.webp","handbook":"https://emilydiazslp.com/wp-content/uploads/2026/04/handbook-scaled.webp","ipadBig":"https://emilydiazslp.com/wp-content/uploads/2026/04/ipad-big.webp","blouse":"https://emilydiazslp.com/wp-content/uploads/2026/04/emily-purple-blouse.webp","ig":"https://emilydiazslp.com/wp-content/uploads/2023/09/instagram.png","fb":"https://emilydiazslp.com/wp-content/uploads/2023/09/facebook.png","yt":"https://emilydiazslp.com/wp-content/uploads/2023/09/youtube.png","login":"https://emilydiazslp.com/wp-content/uploads/2023/09/login.png","icShopping":"https://emilydiazslp.com/wp-content/uploads/2023/08/shopping.png","icHeart":"https://emilydiazslp.com/wp-content/uploads/2023/08/heart.png","icBag":"https://emilydiazslp.com/wp-content/uploads/2023/08/school-bag.png","icSpeech":"https://emilydiazslp.com/wp-content/uploads/2023/08/speech.png","icInterview":"https://emilydiazslp.com/wp-content/uploads/2023/08/job-interview.png","partners":["https://emilydiazslp.com/wp-content/uploads/2023/09/NavyBlueStar_Region13Logo-1024x188.png","https://emilydiazslp.com/wp-content/uploads/2023/09/talkingaac-logo-fullcolor-small_3.png","https://emilydiazslp.com/wp-content/uploads/2023/09/r17_logo_250.png","https://emilydiazslp.com/wp-content/uploads/2023/09/aac-in-the-cloud-1024x1024.jpeg","https://emilydiazslp.com/wp-content/uploads/2023/09/meaningful-speech-1024x576.jpeg","https://emilydiazslp.com/wp-content/uploads/2023/09/speechtherapypd-logo.png"]};
const { Button } = window.EmilyDiazSLPDesignSystem_218dc8;

const NAV = [
  {label:'about me', page:'about'},
  {label:'services', page:'services', children:[{label:'aac services',page:'services'},{label:'business coaching',page:'services'}]},
  {label:'aac courses + guides', page:'courses'},
  {label:'contact me', page:'contact'}
];

function SiteHeader({page,go}){
  const [open,setOpen]=React.useState(false);
  return (
    <header style={{position:'sticky',top:0,zIndex:20,background:'var(--white)',boxShadow:'var(--shadow-xs)'}}>
      <div style={{background:'var(--plum-700)',color:'var(--cream-50)',fontSize:13,letterSpacing:'.12em',textTransform:'uppercase',fontWeight:600,textAlign:'right',padding:'10px var(--gutter)'}}>
        <span style={{opacity:.85}}>member login</span>
      </div>
      <div style={{maxWidth:'var(--container-max)',margin:'0 auto',padding:'18px var(--gutter)',display:'flex',alignItems:'center',gap:'var(--space-6)'}}>
        <a href="#" onClick={e=>{e.preventDefault();go('home');}} style={{flex:'0 0 auto'}}>
          <img src={IMG.logo} alt="Emily Diaz, SLP" style={{width:150,display:'block'}} />
        </a>
        <nav style={{display:'flex',gap:'var(--space-6)',marginLeft:'auto',alignItems:'center'}}>
          {NAV.map(item=>(
            <div key={item.label} style={{position:'relative'}} onMouseEnter={()=>item.children&&setOpen(true)} onMouseLeave={()=>item.children&&setOpen(false)}>
              <a href="#" onClick={e=>{e.preventDefault();go(item.page);}}
                 style={{fontWeight:700,fontSize:15,letterSpacing:'var(--tracking-nav)',color:page===item.page?'var(--rose-500)':'var(--plum-700)'}}>{item.label}</a>
              {item.children&&open&&(
                <div style={{position:'absolute',top:'calc(100% + 12px)',left:-16,background:'var(--white)',boxShadow:'var(--shadow-md)',borderRadius:'var(--radius-md)',padding:'var(--space-3)',display:'flex',flexDirection:'column',gap:'var(--space-2)',minWidth:200}}>
                  {item.children.map(c=>(<a key={c.label} href="#" onClick={e=>{e.preventDefault();go(c.page);setOpen(false);}} style={{fontSize:14,fontWeight:500,color:'var(--plum-700)',padding:'6px 8px'}}>{c.label}</a>))}
                </div>
              )}
            </div>
          ))}
          <Button size="sm" variant="secondary" onClick={()=>go('resources')}>Free AAC Resources</Button>
        </nav>
      </div>
    </header>
  );
}

function SiteFooter({go}){
  const links=[['Free AAC Resources','resources'],['About Me','about'],['Services','services'],['aac courses + guides','courses'],['Contact Me','contact']];
  return (
    <footer style={{background:'var(--plum-700)',color:'var(--cream-50)',padding:'var(--space-8) var(--gutter) var(--space-6)'}}>
      <div style={{maxWidth:'var(--container-max)',margin:'0 auto',display:'grid',gridTemplateColumns:'1.1fr 1fr 1fr',gap:'var(--space-7)',alignItems:'start'}}>
        <div>
          <img src={IMG.logo} alt="Emily Diaz, SLP" style={{width:170,filter:'brightness(0) invert(1)'}} />
          <div style={{display:'flex',gap:'var(--space-3)',marginTop:'var(--space-4)'}}>
            {[IMG.ig,IMG.fb,IMG.yt].map(s=>(<img key={s} src={s} alt="" style={{width:30,height:30,objectFit:'contain',filter:'brightness(0) invert(1)',opacity:.9}} />))}
          </div>
        </div>
        <nav style={{display:'flex',flexDirection:'column',gap:'var(--space-2)'}}>
          {links.map(([l,p])=>(<a key={l} href="#" onClick={e=>{e.preventDefault();go(p);}} style={{color:'var(--cream-50)',fontWeight:600,fontSize:14}}>{l}</a>))}
        </nav>
        <div style={{display:'flex',flexDirection:'column',gap:'var(--space-3)',alignItems:'flex-start'}}>
          <img src={IMG.login} alt="" style={{width:44,filter:'brightness(0) invert(1)'}} />
          <a href="#" onClick={e=>e.preventDefault()} style={{color:'var(--blush-300)',fontWeight:700,fontSize:18}}>Student Login</a>
        </div>
      </div>
      <div style={{maxWidth:'var(--container-max)',margin:'var(--space-7) auto 0',borderTop:'1px solid var(--border-inverse)',paddingTop:'var(--space-4)',fontSize:12,opacity:.75,display:'flex',gap:'var(--space-4)',flexWrap:'wrap'}}>
        <span>© 2024 Emily Diaz, SLP | All rights Reserved.</span><span>Privacy Policy</span><span>Terms of Use</span><span>Website Disclaimer</span>
      </div>
    </footer>
  );
}

function Section({tone='page',children,style}){
  const bg={page:'var(--surface-page)',white:'var(--white)',tint:'var(--blush-100)',plum:'var(--plum-700)'}[tone];
  return (<section style={{background:bg,padding:'var(--section-y) var(--gutter)',...style}}>
    <div style={{maxWidth:'var(--container-max)',margin:'0 auto'}}>{children}</div>
  </section>);
}

Object.assign(window,{SiteHeader,SiteFooter,Section,IMG});
