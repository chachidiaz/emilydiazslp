const { Button, SectionHeading, ServiceCard, QuoteBlock } = window.EmilyDiazSLPDesignSystem_218dc8;

function HomeScreen({go}){
  return (
    <main>
      <section style={{background:'var(--cream-50)',padding:'var(--space-8) var(--gutter) 0',overflow:'hidden'}}>
        <div style={{maxWidth:'var(--container-max)',margin:'0 auto',display:'grid',gridTemplateColumns:'1.05fr .95fr',gap:'var(--space-7)',alignItems:'end'}}>
          <div style={{paddingBottom:'var(--space-9)'}}>
            <h1 style={{fontSize:'var(--text-display)',fontWeight:700,lineHeight:1.15,color:'var(--plum-700)',margin:0}}>Everyone deserves a</h1>
            <div style={{fontFamily:'var(--font-script)',fontSize:'var(--text-script-xl)',lineHeight:.85,color:'var(--rose-500)',margin:'4px 0 8px'}}>voice.</div>
            <h2 style={{fontSize:'var(--text-h2)',fontWeight:700,color:'var(--plum-700)',margin:'0 0 var(--space-4)'}}>We can help find it.</h2>
            <p style={{fontSize:'var(--text-body-lg)',lineHeight:'var(--leading-loose)',color:'var(--text-body)',maxWidth:'52ch'}}>Our practice values neurodiversity affirming services and specializes in providing support for AAC, social communication, &amp; gestalt language processing. Whether it's therapy or consulting, we are committed to making your journey feel more comfortable and helping you feel more confident.</p>
            <div style={{display:'flex',gap:'var(--space-3)',flexWrap:'wrap',marginTop:'var(--space-5)'}}>
              <Button variant="primary" size="lg" onClick={()=>go('resources')}>Resources for SLPs</Button>
              <Button variant="outline" size="lg" onClick={()=>go('services')}>Services for Families</Button>
            </div>
          </div>
          <div style={{position:'relative'}}>
            <div style={{position:'absolute',inset:'12% 6% 0 6%',background:'var(--blush-100)',borderRadius:'var(--radius-blob)'}}></div>
            <img src={IMG.hero} alt="Emily Diaz" style={{position:'relative',width:'100%',display:'block'}} />
          </div>
        </div>
      </section>

      <Section tone="plum">
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'var(--space-7)',alignItems:'center'}}>
          <h2 style={{fontSize:'var(--text-h2)',fontWeight:700,color:'var(--cream-50)',margin:0,lineHeight:1.25}}>We know how challenging it can be to navigate unique &amp; complex communication needs.</h2>
          <p style={{fontSize:'var(--text-body-lg)',lineHeight:'var(--leading-loose)',color:'rgba(255,251,253,.85)',margin:0}}>There's a lot out there when it comes to learning about topics like communication devices, echolalia, and language delays. You could spend hours researching it all yourself and hopping from therapist to therapist… or you could let us guide you along the way.</p>
        </div>
      </Section>

      <Section tone="page">
        <SectionHeading align="center" eyebrow="ready to work together?" script="here's how I can help." />
        <div style={{display:'grid',gridTemplateColumns:'.9fr 1.1fr',gap:'var(--space-7)',alignItems:'center',marginTop:'var(--space-7)'}}>
          <img src={IMG.ipad} alt="AAC device mockup" style={{width:'100%',display:'block'}} />
          <div style={{display:'flex',flexDirection:'column',gap:'var(--space-4)'}}>
            {[['Evaluations & therapy services','Our services are focused on supporting adult and pediatric AAC, gestalt language processing, social communication skills, and other speech/language needs like stuttering, speech sound disorders, and language delays.'],
              ['1:1 Coaching Packages','Communication partner interventions are critical when it comes to understanding AAC implementation or the framework of gestalt language processing. By working together 1:1 over 12 weeks, we can help you become a more confident communication partner.'],
              ['School Contracts + Consulting','We provide special education services through school contracts for speech and language services, assistive technology policies, classroom support, team training, and Independent Educational Evaluations (IEE).']]
              .map(([t,b])=>(
              <article key={t} style={{background:'var(--white)',borderRadius:'var(--radius-lg)',padding:'var(--space-5)',boxShadow:'var(--shadow-sm)'}}>
                <h3 style={{margin:'0 0 var(--space-2)',fontSize:'var(--text-h4)',fontWeight:700,color:'var(--plum-700)'}}>{t}</h3>
                <p style={{margin:'0 0 var(--space-3)',fontSize:'var(--text-body-sm)',lineHeight:'var(--leading-loose)'}}>{b}</p>
                <a href="#" onClick={e=>{e.preventDefault();go('services');}} style={{fontWeight:700,fontSize:13,letterSpacing:'var(--tracking-eyebrow)',textTransform:'uppercase'}}>learn more</a>
              </article>))}
          </div>
        </div>
      </Section>

      <Section tone="tint">
        <div style={{textAlign:'center'}}>
          <div style={{fontFamily:'var(--font-script)',fontSize:'var(--text-script-lg)',color:'var(--plum-700)',lineHeight:.95}}>we're ready and available to help.</div>
          <p style={{maxWidth:'60ch',margin:'var(--space-4) auto var(--space-5)',fontSize:'var(--text-body-lg)',lineHeight:'var(--leading-loose)',color:'var(--plum-900)'}}>With years of experience, and being a leader in the field, we offer a full range of AAC solutions to fit any need.</p>
          <Button variant="primary" size="lg" onClick={()=>go('services')}>See a full list of services</Button>
        </div>
      </Section>

      <Section tone="white">
        <h3 style={{textAlign:'center',fontSize:'var(--text-h3)',fontWeight:700,color:'var(--plum-700)',marginBottom:'var(--space-6)'}}>I've worked with these incredible organizations</h3>
        <div style={{display:'grid',gridTemplateColumns:'repeat(6,1fr)',gap:'var(--space-6)',alignItems:'center'}}>
          {IMG.partners.map(p=>(<img key={p} src={p} alt="" style={{width:'100%',maxHeight:70,objectFit:'contain',filter:'grayscale(1)',opacity:.65}} />))}
        </div>
      </Section>

      <Section tone="page">
        <div style={{display:'grid',gridTemplateColumns:'1fr .85fr',gap:'var(--space-8)',alignItems:'center'}}>
          <div>
            <div className="eyebrow">about emily</div>
            <h2 style={{fontSize:'var(--text-h2)',fontWeight:700,color:'var(--plum-700)',margin:'var(--space-3) 0 var(--space-4)',lineHeight:1.25}}>"I'm just a human trying to make a connection to other humans."</h2>
            <p style={{fontSize:'var(--text-body-lg)',lineHeight:'var(--leading-loose)'}}>Hey there, it's Emily! I'm a speech-language pathologist with a passion for complex communicators and a desire to build connection and confidence through AAC. I've worked in public schools, residential treatment centers, and private practice since 2015 with kids and young adults who just want to be loved &amp; heard.</p>
            <Button variant="secondary" onClick={()=>go('about')}>More about Emily</Button>
          </div>
          <img src={IMG.pro} alt="Emily Diaz" style={{width:'100%',borderRadius:'var(--radius-xl)',boxShadow:'var(--shadow-md)',display:'block'}} />
        </div>
      </Section>
    </main>
  );
}
window.HomeScreen = HomeScreen;
