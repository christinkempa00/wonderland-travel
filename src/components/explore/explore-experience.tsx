"use client";

import { useMemo, useState } from "react";
import { Building2, Car, Plane } from "lucide-react";
import { Accent, SectionHeader, Select } from "@/components/ui";
import { cn } from "@/lib/cn";
import {
  FLIGHTS,
  HOTELS,
  RENTALS,
  filterAndSort,
  type ExploreCategory,
  type FlightItem,
  type HotelItem,
  type PriceBucket,
  type RentalItem,
  type SortOption,
} from "./data";
import { FlightCard } from "./flight-card";
import { PhotoResultCard } from "./result-card";
import { SummaryPanel } from "./summary-panel";

const TABS = [
  { id: "hotel" as const, label: "Hotel", icon: Building2 },
  { id: "pesawat" as const, label: "Pesawat", icon: Plane },
  { id: "rental" as const, label: "Rental", icon: Car },
];

interface Selections {
  hotel: HotelItem | null;
  pesawat: FlightItem | null;
  rental: RentalItem | null;
}

export function ExploreExperience() {
  const [activeTab, setActiveTab] = useState<ExploreCategory>("hotel");
  const [location, setLocation] = useState("all");
  const [priceBucket, setPriceBucket] = useState<PriceBucket>("all");
  const [sort, setSort] = useState<SortOption>("rekomendasi");
  const [selections, setSelections] = useState<Selections>({
    hotel: null,
    pesawat: null,
    rental: null,
  });

  function handleTabChange(tab: ExploreCategory) {
    setActiveTab(tab);
    setLocation("all");
    setPriceBucket("all");
    setSort("rekomendasi");
  }

  function handleRemove(category: ExploreCategory) {
    setSelections((prev) => ({ ...prev, [category]: null }));
  }

  const locationOptions = useMemo(() => {
    const items = activeTab === "hotel" ? HOTELS : activeTab === "pesawat" ? FLIGHTS : RENTALS;
    return Array.from(new Set(items.map((item) => item.location)));
  }, [activeTab]);

  const hotelResults = useMemo(
    () => filterAndSort(HOTELS, location, priceBucket, sort),
    [location, priceBucket, sort],
  );
  const flightResults = useMemo(
    () => filterAndSort(FLIGHTS, location, priceBucket, sort),
    [location, priceBucket, sort],
  );
  const rentalResults = useMemo(
    () => filterAndSort(RENTALS, location, priceBucket, sort),
    [location, priceBucket, sort],
  );

  const activeCount =
    activeTab === "hotel"
      ? hotelResults.length
      : activeTab === "pesawat"
        ? flightResults.length
        : rentalResults.length;

  return (
    <section className="mx-auto flex w-full max-w-7xl flex-col gap-10 px-6 pb-16 pt-16 md:pb-20 md:pt-20">
      <SectionHeader
        align="left"
        badge="Explore"
        title={
          <>
            Booking <Accent>Gabungan</Accent> Dalam Satu Genggaman
          </>
        }
        description="Pilih hotel, penerbangan, dan rental mobil sekaligus — lalu kirim pesananmu langsung ke tim kami via WhatsApp."
      />

      <div className="flex gap-2 border-b border-border">
        {TABS.map((tab) => (
          <button
            key={tab.id}
            type="button"
            onClick={() => handleTabChange(tab.id)}
            className={cn(
              "flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-semibold transition-colors",
              activeTab === tab.id
                ? "border-black text-heading"
                : "border-transparent text-muted hover:text-heading",
            )}
          >
            <tab.icon className="size-4" aria-hidden="true" />
            {tab.label}
          </button>
        ))}
      </div>

      <div className="flex flex-wrap items-end gap-4">
        <Select
          label="Lokasi"
          value={location}
          onChange={(event) => setLocation(event.target.value)}
        >
          <option value="all">Semua Lokasi</option>
          {locationOptions.map((loc) => (
            <option key={loc} value={loc}>
              {loc}
            </option>
          ))}
        </Select>
        <Select
          label="Harga"
          value={priceBucket}
          onChange={(event) => setPriceBucket(event.target.value as PriceBucket)}
        >
          <option value="all">Semua Harga</option>
          <option value="low">Di bawah Rp 500rb</option>
          <option value="mid">Rp 500rb – Rp 1jt</option>
          <option value="high">Di atas Rp 1jt</option>
        </Select>
        <Select label="Urutkan" value={sort} onChange={(event) => setSort(event.target.value as SortOption)}>
          <option value="rekomendasi">Rekomendasi</option>
          <option value="harga-rendah">Harga Terendah</option>
          <option value="harga-tinggi">Harga Tertinggi</option>
          <option value="rating">Rating Tertinggi</option>
        </Select>
      </div>

      <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1fr_320px] lg:items-start">
        <div className="flex snap-x snap-mandatory gap-6 overflow-x-auto pb-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
          {activeCount === 0 && (
            <p className="py-12 text-sm text-muted">
              Tidak ada hasil untuk filter ini. Coba ubah lokasi atau rentang harga.
            </p>
          )}

          {activeTab === "hotel" &&
            hotelResults.map((hotel) => (
              <PhotoResultCard
                key={hotel.id}
                image={hotel.image}
                name={hotel.name}
                location={hotel.location}
                price={hotel.price}
                priceUnit="/malam"
                rating={hotel.rating}
                specs={hotel.facilities}
                selected={selections.hotel?.id === hotel.id}
                onSelect={() => setSelections((prev) => ({ ...prev, hotel }))}
              />
            ))}

          {activeTab === "pesawat" &&
            flightResults.map((flight) => (
              <FlightCard
                key={flight.id}
                airline={flight.name}
                from={flight.from}
                to={flight.to}
                departTime={flight.departTime}
                arriveTime={flight.arriveTime}
                duration={flight.duration}
                travelClass={flight.travelClass}
                price={flight.price}
                rating={flight.rating}
                selected={selections.pesawat?.id === flight.id}
                onSelect={() => setSelections((prev) => ({ ...prev, pesawat: flight }))}
              />
            ))}

          {activeTab === "rental" &&
            rentalResults.map((rental) => (
              <PhotoResultCard
                key={rental.id}
                image={rental.image}
                name={rental.name}
                location={rental.location}
                price={rental.price}
                priceUnit="/hari"
                rating={rental.rating}
                specs={[rental.type, rental.transmission, `${rental.seats} kursi`]}
                selected={selections.rental?.id === rental.id}
                onSelect={() => setSelections((prev) => ({ ...prev, rental }))}
              />
            ))}
        </div>

        <div className="lg:sticky lg:top-28">
          <SummaryPanel
            hotel={selections.hotel}
            pesawat={selections.pesawat}
            rental={selections.rental}
            onRemove={handleRemove}
          />
        </div>
      </div>
    </section>
  );
}
