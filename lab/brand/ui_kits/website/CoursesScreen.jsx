const { CourseCard, SectionHeading } = window.EmilyDiazSLPDesignSystem_218dc8;

const COURSES=[
  {kind:'signature course',title:'The AAC Assessment Hub',body:'The AAC Assessment Hub provides a step-by-step process for conducting assessments from start to finish, along with data sheets, downloads, and more.',image:IMG.hub},
  {kind:'pdf download',title:'The AAC Handbook',body:'A must-have digital resource full of simple handouts about a full range of AAC topics that do the explaining for you.',image:IMG.handbook},
  {kind:'workshop',title:"When AAC Isn't Working",body:'Stop the cycle of AAC abandonment. This new protocol identifies the system leaks and sparks a RESET that leads to successful AAC implementation.',image:IMG.ipadBig},
  {kind:'workshop',title:'Introduction to AAC for GLP',body:'A 2 hour training for parents and professionals to expand their knowledge about AAC best practices and how to support GLPs!',image:IMG.blouse}
];

function CoursesScreen(){
  return (
    <main>
      <Section tone="page">
        <SectionHeading align="center" script="the fast track to aac confidence starts here." />
        <div style={{display:'flex',flexDirection:'column',gap:'var(--space-6)',marginTop:'var(--space-7)'}}>
          {COURSES.map((c,i)=>(
            <CourseCard key={c.title} layout="horizontal" kind={c.kind} title={c.title} body={c.body} image={c.image} imageAlt={c.title} ctaHref="#" reverse={i%2===1} />
          ))}
        </div>
      </Section>
    </main>
  );
}
window.CoursesScreen = CoursesScreen;
