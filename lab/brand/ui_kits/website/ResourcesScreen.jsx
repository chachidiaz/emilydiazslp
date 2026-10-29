const { Input, Button, SectionHeading, Card, BulletList } = window.EmilyDiazSLPDesignSystem_218dc8;

function ResourcesScreen(){
  const [done,setDone]=React.useState(false);
  return (
    <main>
      <Section tone="tint">
        <div style={{display:'grid',gridTemplateColumns:'1.05fr .95fr',gap:'var(--space-8)',alignItems:'center'}}>
          <div>
            <SectionHeading eyebrow="free aac resources" script="start here, for free." body="Grab the handouts and data sheets I use every week with SLPs, teachers and families." />
            <BulletList marker="check" tone="light" style={{marginTop:'var(--space-5)'}} items={['AAC implementation checklist','Staff buy-in one-pager','Gestalt language processing primer']} />
          </div>
          <Card tone="white" elevation="lg" padding="var(--space-7)">
            {done ? (
              <div style={{textAlign:'center'}}>
                <div style={{fontFamily:'var(--font-script)',fontSize:'var(--text-script-md)',color:'var(--rose-500)'}}>check your inbox!</div>
                <p style={{marginTop:'var(--space-3)'}}>Your free resources are on the way.</p>
              </div>
            ) : (
              <form onSubmit={e=>{e.preventDefault();setDone(true);}} style={{display:'flex',flexDirection:'column',gap:'var(--space-4)'}}>
                <h3 style={{margin:0}}>Send me the free resources</h3>
                <Input label="First name" placeholder="Emily" />
                <Input label="Email" type="email" placeholder="you@school.org" />
                <Button variant="secondary" size="lg" fullWidth type="submit">send them to me</Button>
              </form>
            )}
          </Card>
        </div>
      </Section>
    </main>
  );
}
window.ResourcesScreen = ResourcesScreen;
