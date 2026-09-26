// Icons the server can name (ManageMenu icon: 'inbox') and the section-type pictures. Only what is used is bundled.
import {
    BarChart3, Building2, Camera, Circle, Clock, CreditCard, Dumbbell, GraduationCap, HeartHandshake, HelpCircle, Image, Inbox,
    LayoutDashboard, LayoutGrid, LayoutTemplate, Mail, Megaphone, MapPin, Receipt, Scale, Scissors, ShoppingBag, Sparkles, Star,
    Stethoscope, Table2, Type, Users, UsersRound, UtensilsCrossed, Shield, Layers, Wrench,
} from 'lucide-vue-next';

const menu = {
    'layout-dashboard': LayoutDashboard, 'layout-template': LayoutTemplate, 'building-2': Building2,
    inbox: Inbox, users: Users, shield: Shield, layers: Layers, circle: Circle,
};

export const menuIcon = (name) => menu[name] ?? Circle;

const sections = {
    hero: Sparkles, stats: BarChart3, text: Type, list: LayoutGrid, plans: CreditCard, priced_list: Receipt,
    gallery: Image, reviews: Star, faq: HelpCircle, hours: Clock, cta: Megaphone, contact_info: MapPin, contact_form: Mail,
    comparison_table: Table2, team: UsersRound,
};

export const sectionIcon = (type) => sections[type] ?? LayoutTemplate;

// the business-kind tiles on "new client" (config platform.business_kinds)
const kinds = {
    wrench: Wrench, utensils: UtensilsCrossed, stethoscope: Stethoscope, scale: Scale, scissors: Scissors, dumbbell: Dumbbell,
    camera: Camera, 'shopping-bag': ShoppingBag, 'graduation-cap': GraduationCap, 'heart-handshake': HeartHandshake, sparkles: Sparkles,
};

export const kindIcon = (name) => kinds[name] ?? Sparkles;
