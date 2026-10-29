const { Button, BulletList, QuoteBlock } = window.EmilyDiazSLPDesignSystem_218dc8;

function AboutScreen({go}){
  return (
    <main>
      <Section tone="page">
        <div style={{display:'grid',gridTemplateColumns:'.9fr 1.1fr',gap:'var(--space-8)',alignItems:'center'}}>
          <img src={IMG.aboutEmily} alt="Emily Diaz" style={{width:'100%',borderRadius:'var(--radius-xl)',display:'block'}} />
          <div>
            <h1 style={{fontSize:'var(--text-h2)',fontWeight:700,color:'var(--plum-700)',margin:0,lineHeight:1.25}}>I share about what's worked for me because…</h1>
            <div style={{fontFamily:'var(--font-script)',fontSize:'var(--text-script-lg)',color:'var(--rose-500)',lineHeight:.95,margin:'var(--space-3) 0 var(--space-5)'}}>I believe it can work for you too.</div>
            <p>I'm an AAC Specialist, but I have a confession to make. I started working in this field like most SLPs do — with absolutely no education or training on complex communication needs or assistive technology. In 2015, I took over a caseload of students I had no clue how to treat. I constantly questioned my evaluations, my IEP goals, my interventions, and my knowledge about AAC. I wasn't "learning on the job" either.</p>
            <p><strong>I was failing.</strong> From grant writing, to lending libraries, to workshops and everything in between I somehow managed to take control of my caseload and fall in love with AAC.</p>
            <p>I know from experience how it feels to know nothing about devices, have zero staff buy-in, and no time to train teachers and paraprofessionals. I also know that it's possible to change and stop letting AAC scare you and stress you out.</p>
          </div>
        </div>
      </Section>

      <Section tone="white">
        <h3 style={{textAlign:'center',fontSize:'var(--text-h3)',fontWeight:700,color:'var(--plum-700)',marginBottom:'var(--space-6)'}}>I've been featured at…</h3>
        <div style={{display:'grid',gridTemplateColumns:'repeat(6,1fr)',gap:'var(--space-6)',alignItems:'center'}}>
          {IMG.partners.map(p=>(<img key={p} src={p} alt="" style={{width:'100%',maxHeight:64,objectFit:'contain',filter:'grayscale(1)',opacity:.6}} />))}
        </div>
      </Section>

      <Section tone="tint">
        <div style={{display:'grid',gridTemplateColumns:'.8fr 1.2fr',gap:'var(--space-8)',alignItems:'center'}}>
          <img src={IMG.edited} alt="Emily Diaz" style={{width:'100%',display:'block'}} />
          <div>
            <div className="eyebrow">professional bio</div>
            <p style={{marginTop:'var(--space-4)',color:'var(--plum-900)'}}>Emily Diaz, M.S. CCC-SLP is a certified speech-language pathologist with a passion for empowering individuals to find their voice. With a focus on neurodiversity affirming care and augmentative &amp; alternative communication (AAC), Emily offers valuable insight and support to clinicians, parents, and school districts.</p>
            <p style={{color:'var(--plum-900)'}}>Emily's expertise extends to topics like managing AAC in the classroom, the importance of staff buy-in and dynamic coaching methods, and the nuances of gestalt language processing and AAC implementation strategies.</p>
            <Button variant="primary" onClick={()=>go('services')}>see the full list of services</Button>
          </div>
        </div>
      </Section>

      <Section tone="page">
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'var(--space-8)',alignItems:'center'}}>
          <div>
            <div className="eyebrow">slp, mom, wife &amp; more</div>
            <div style={{fontFamily:'var(--font-script)',fontSize:'var(--text-script-md)',color:'var(--plum-700)',margin:'var(--space-2) 0 var(--space-5)'}}>a little more about Emily</div>
            <BulletList marker="check" items={['My kids have an 8 year age gap','Coffee order: vanilla almond milk latte, half sweet','Most likely to be found roaming Target aisles','Big Little League Baseball fan right now','I thrive on routines &amp; systems']} />
          </div>
          <QuoteBlock quote="I'm just a human trying to make a connection to other humans." attribution="Emily Diaz" role="M.S. CCC-SLP, AAC Specialist" />
        </div>
      </Section>
    </main>
  );
}
window.AboutScreen = AboutScreen;
