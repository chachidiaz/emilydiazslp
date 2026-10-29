const { Input, Textarea, Select, Checkbox, Button, SectionHeading, Card } = window.EmilyDiazSLPDesignSystem_218dc8;

function ContactScreen(){
  const [sent,setSent]=React.useState(false);
  return (
    <main>
      <Section tone="page">
        <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'var(--space-8)',alignItems:'start'}}>
          <div>
            <SectionHeading eyebrow="contact me" script="let's find the right next step." body="Tell me a little about who you're supporting and I'll point you toward the service, course, or free resource that fits." />
            <img src={IMG.flourish} alt="" style={{width:140,marginTop:'var(--space-6)',opacity:.9}} />
          </div>
          <Card tone="white" elevation="md" padding="var(--space-7)">
            {sent ? (
              <div style={{textAlign:'center',padding:'var(--space-6) 0'}}>
                <div style={{fontFamily:'var(--font-script)',fontSize:'var(--text-script-md)',color:'var(--rose-500)'}}>thank you!</div>
                <p style={{marginTop:'var(--space-3)'}}>I'll be in touch within two business days.</p>
                <Button variant="ghost" onClick={()=>setSent(false)}>send another message</Button>
              </div>
            ) : (
              <form onSubmit={e=>{e.preventDefault();setSent(true);}} style={{display:'flex',flexDirection:'column',gap:'var(--space-4)'}}>
                <div style={{display:'grid',gridTemplateColumns:'1fr 1fr',gap:'var(--space-4)'}}>
                  <Input label="First name" placeholder="Emily" />
                  <Input label="Last name" placeholder="Diaz" />
                </div>
                <Input label="Email" type="email" placeholder="you@school.org" hint="I'll never share it." />
                <Select label="I am a…" options={['SLP or related professional','Parent / caregiver','School district','Someone else']} />
                <Textarea label="How can I help?" rows={4} />
                <Checkbox label="Send me free AAC resources by email" />
                <Button variant="primary" size="lg" fullWidth type="submit">send it over</Button>
              </form>
            )}
          </Card>
        </div>
      </Section>
    </main>
  );
}
window.ContactScreen = ContactScreen;
