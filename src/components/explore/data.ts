export type ExploreCategory = "hotel" | "pesawat" | "rental";

export interface HotelItem {
  id: string;
  name: string;
  location: string;
  price: number;
  rating: number;
  image: string;
  facilities: string[];
}

export interface FlightItem {
  id: string;
  name: string;
  location: string;
  price: number;
  rating: number;
  from: string;
  to: string;
  departTime: string;
  arriveTime: string;
  duration: string;
  travelClass: string;
}

export interface RentalItem {
  id: string;
  name: string;
  location: string;
  price: number;
  rating: number;
  image: string;
  type: string;
  transmission: string;
  seats: number;
}

export { WHATSAPP_NUMBER } from "@/lib/whatsapp";

// TODO: ganti data riil klien — HOTELS, FLIGHTS, RENTALS, dan semua harganya
// di bawah ini masih data contoh (mock), bukan inventori sungguhan. Sambungkan
// ke sumber data/API pemesanan yang sebenarnya sebelum go-live.
export const HOTELS: HotelItem[] = [
  {
    id: "hotel-ubud-retreat",
    name: "The Ubud Retreat",
    location: "Bali",
    price: 850000,
    rating: 4.8,
    image: "wonderland-hotel-ubud",
    facilities: ["WiFi", "Kolam Renang", "Sarapan"],
  },
  {
    id: "hotel-malioboro-boutique",
    name: "Malioboro Boutique Hotel",
    location: "Yogyakarta",
    price: 450000,
    rating: 4.5,
    image: "wonderland-hotel-malioboro",
    facilities: ["WiFi", "AC", "Sarapan"],
  },
  {
    id: "hotel-komodo-bay",
    name: "Komodo Bay Resort",
    location: "Labuan Bajo",
    price: 1200000,
    rating: 4.9,
    image: "wonderland-hotel-komodobay",
    facilities: ["WiFi", "Kolam Renang", "Pantai Pribadi"],
  },
  {
    id: "hotel-senggigi-beach",
    name: "Senggigi Beach Hotel",
    location: "Lombok",
    price: 650000,
    rating: 4.6,
    image: "wonderland-hotel-senggigi",
    facilities: ["WiFi", "Pantai", "Sarapan"],
  },
  {
    id: "hotel-toba-lake-view",
    name: "Toba Lake View Inn",
    location: "Danau Toba",
    price: 500000,
    rating: 4.4,
    image: "wonderland-hotel-toba",
    facilities: ["WiFi", "Danau View"],
  },
  {
    id: "hotel-bromo-highland",
    name: "Bromo Highland Lodge",
    location: "Bromo",
    price: 400000,
    rating: 4.3,
    image: "wonderland-hotel-bromo",
    facilities: ["WiFi", "Pemanas Ruangan"],
  },
];

export const FLIGHTS: FlightItem[] = [
  {
    id: "flight-garuda-dps-pagi",
    name: "Garuda Indonesia",
    location: "Jakarta → Denpasar",
    price: 1100000,
    rating: 4.7,
    from: "CGK",
    to: "DPS",
    departTime: "06:00",
    arriveTime: "09:00",
    duration: "2j 0m",
    travelClass: "Ekonomi",
  },
  {
    id: "flight-citilink-yia",
    name: "Citilink",
    location: "Jakarta → Yogyakarta",
    price: 650000,
    rating: 4.3,
    from: "CGK",
    to: "YIA",
    departTime: "08:30",
    arriveTime: "10:00",
    duration: "1j 30m",
    travelClass: "Ekonomi",
  },
  {
    id: "flight-batik-lbj",
    name: "Batik Air",
    location: "Jakarta → Labuan Bajo",
    price: 1450000,
    rating: 4.6,
    from: "CGK",
    to: "LBJ",
    departTime: "05:45",
    arriveTime: "09:15",
    duration: "2j 30m",
    travelClass: "Ekonomi",
  },
  {
    id: "flight-lion-kno",
    name: "Lion Air",
    location: "Jakarta → Medan",
    price: 950000,
    rating: 4.1,
    from: "CGK",
    to: "KNO",
    departTime: "07:00",
    arriveTime: "09:30",
    duration: "2j 30m",
    travelClass: "Ekonomi",
  },
  {
    id: "flight-garuda-dps-siang",
    name: "Garuda Indonesia",
    location: "Jakarta → Denpasar",
    price: 1350000,
    rating: 4.8,
    from: "CGK",
    to: "DPS",
    departTime: "14:00",
    arriveTime: "17:00",
    duration: "2j 0m",
    travelClass: "Bisnis",
  },
];

export const RENTALS: RentalItem[] = [
  {
    id: "rental-avanza",
    name: "Toyota Avanza",
    location: "Bali",
    price: 350000,
    rating: 4.5,
    image: "wonderland-rental-avanza",
    type: "MPV",
    transmission: "Manual",
    seats: 6,
  },
  {
    id: "rental-brio",
    name: "Honda Brio",
    location: "Yogyakarta",
    price: 275000,
    rating: 4.4,
    image: "wonderland-rental-brio",
    type: "City Car",
    transmission: "Automatic",
    seats: 4,
  },
  {
    id: "rental-fortuner",
    name: "Toyota Fortuner",
    location: "Labuan Bajo",
    price: 750000,
    rating: 4.8,
    image: "wonderland-rental-fortuner",
    type: "SUV",
    transmission: "Automatic",
    seats: 7,
  },
  {
    id: "rental-xenia",
    name: "Daihatsu Xenia",
    location: "Bali",
    price: 320000,
    rating: 4.3,
    image: "wonderland-rental-xenia",
    type: "MPV",
    transmission: "Manual",
    seats: 6,
  },
  {
    id: "rental-pajero",
    name: "Mitsubishi Pajero Sport",
    location: "Danau Toba",
    price: 800000,
    rating: 4.7,
    image: "wonderland-rental-pajero",
    type: "SUV",
    transmission: "Automatic",
    seats: 7,
  },
];

export function formatIDR(value: number): string {
  return `Rp ${value.toLocaleString("id-ID")}`;
}

export type PriceBucket = "all" | "low" | "mid" | "high";
export type SortOption = "rekomendasi" | "harga-rendah" | "harga-tinggi" | "rating";

export function filterAndSort<T extends { location: string; price: number; rating: number }>(
  items: T[],
  location: string,
  priceBucket: PriceBucket,
  sort: SortOption,
): T[] {
  const filtered = items
    .filter((item) => location === "all" || item.location === location)
    .filter((item) => {
      if (priceBucket === "all") return true;
      if (priceBucket === "low") return item.price < 500000;
      if (priceBucket === "mid") return item.price >= 500000 && item.price <= 1000000;
      return item.price > 1000000;
    });

  const sorted = [...filtered];
  if (sort === "harga-rendah") sorted.sort((a, b) => a.price - b.price);
  else if (sort === "harga-tinggi") sorted.sort((a, b) => b.price - a.price);
  else if (sort === "rating") sorted.sort((a, b) => b.rating - a.rating);

  return sorted;
}
