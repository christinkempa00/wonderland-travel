import { AboutStats } from "@/components/home/about-stats";
import { Cta } from "@/components/home/cta";
import { FeaturedPackages } from "@/components/home/featured-packages";
import { Hero } from "@/components/home/hero";
import { Testimonials } from "@/components/home/testimonials";
import { WhyWonderland } from "@/components/home/why-wonderland";

export default function Home() {
  return (
    <>
      <Hero />
      <AboutStats />
      <WhyWonderland />
      <FeaturedPackages />
      <Testimonials />
      <Cta />
    </>
  );
}
