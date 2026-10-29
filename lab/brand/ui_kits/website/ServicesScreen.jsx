const { SectionHeading, ServiceCard } = window.EmilyDiazSLPDesignSystem_218dc8;

const SERVICES=[
  {icon:IMG.icShopping,title:'AAC Evaluations',body:"Augmentative & Alternative Communication (AAC) can be a game changer in finding your child's voice. This process starts with a 60-minute evaluation to explore different AAC options in a play-based environment. The trial phase comes next — a few options over 4–6 weeks — before helping you navigate the funding process.",cta:'Book a free consult'},
  {icon:IMG.icHeart,title:'Speech & Language Therapy',body:'Our therapy services are individualized to meet your unique needs in the areas of AAC, Gestalt Language Processing, and Social Communication. We provide services in-home and virtually in Arizona, Texas, and Indiana.',cta:'request more info'},
  {icon:IMG.icBag,title:'School Contracts & Consulting',body:'We contract with schools across the United States to provide training and support communication needs in the classroom setting. Our contracted services range from virtual trainings to in-person therapy & evaluations.',cta:'Request my Pricing Guide'},
  {icon:IMG.icSpeech,title:'Professional Development & Speaking',body:'Our live courses focus on equipping SLPs and related professions to confidently support individuals who use AAC and to learn more about gestalt language processing.',cta:'click to Learn more'},
  {icon:IMG.icInterview,title:'1:1 Coaching & Consultations',body:'Next-level support for professionals and parents looking for individualized guidance with AAC. Choose from a 12-week coaching program or a one-off consultation.',cta:'book a free consult'}
];

function ServicesScreen({go}){
  return (
    <main>
      <Section tone="page">
        <SectionHeading align="center" eyebrow="how can i help?" title="AAC Services" script="let's tackle aac together" />
        <div style={{display:'grid',gridTemplateColumns:'repeat(3,1fr)',gap:'var(--space-5)',marginTop:'var(--space-7)'}}>
          {SERVICES.map((s,i)=>(
            <ServiceCard key={s.title} tone={i===4?'tint':'white'} icon={<img src={s.icon} alt="" style={{width:40,height:40,objectFit:'contain'}} />} title={s.title} body={s.body} ctaLabel={s.cta} />
          ))}
        </div>
      </Section>
      <Section tone="plum">
        <div style={{textAlign:'center'}}>
          <div style={{fontFamily:'var(--font-script)',fontSize:'var(--text-script-lg)',color:'var(--blush-300)',lineHeight:.95}}>not sure where to start?</div>
          <p style={{color:'rgba(255,251,253,.85)',maxWidth:'56ch',margin:'var(--space-4) auto var(--space-5)',fontSize:'var(--text-body-lg)'}}>Book a free 15-minute consult and we'll figure out the next right step together.</p>
          <a href="#" onClick={e=>{e.preventDefault();go('contact');}} style={{display:'inline-block',background:'var(--blush-300)',color:'var(--plum-900)',fontWeight:700,padding:'18px 40px',borderRadius:'var(--radius-pill)'}}>contact me</a>
        </div>
      </Section>
    </main>
  );
}
window.ServicesScreen = ServicesScreen;
