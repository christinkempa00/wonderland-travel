import type { Metadata } from "next";
import { ExploreExperience } from "@/components/explore/explore-experience";

export const metadata: Metadata = {
  title: "Explore — Wonderland Travel",
  description: "Booking gabungan hotel, penerbangan, dan rental mobil dalam satu tempat.",
};

export default function ExplorePage() {
  return <ExploreExperience />;
}
