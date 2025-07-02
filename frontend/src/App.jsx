import {
  BrowserRouter as Router,
  Routes,
  Route,
  useLocation,
} from "react-router-dom";
import Header from "./components/Layout/Header";
import Footer from "./components/Layout/Footer";
import Home from "./components/pages/Home";
import About from "./components/pages/About";
import Services from "./components/pages/Services";
import Tickets from "./components/pages/Tickets";
import News from "./components/pages/News";
import Contact from "./components/pages/Contact";
import Careers from "./components/pages/Careers";
import Orders from "./components/pages/Orders";
import SearchResults from "./components/pages/SearchResults";
import SignUp from "./components/Account/SignUp";
import SignIn from "./components/Account/SignIn";
import { AuthProvider } from "./components/contexts/AuthContext";
import { NotificationProvider } from "./components/contexts/NotificationContext";
import ForgotPassword from "./components/Account/ForgotPassword";
import ResetPassword from "./components/Account/ResetPassword";
import ScrollToTop from "./components/ScrollToTop";
import PaymentStatus from "./components/PaymentStatus";
import CSKH from "./components/Shared/CSKH";
import ToastNotification from "./components/Shared/ToastNotification";
import NewsDetail from "./components/News/NewsDetail";
import ProFile from "./components/Account/ProFile";
import TicketHistory from "./components/Account/TicketHistory";
import TicketDetail from "./components/pages/TicketDetail";
import NotificationPage from "./components/pages/NotificationPage";




function Layout() {
  const location = useLocation();

  return (
    <>
      
      {location.pathname !== "/signup" && location.pathname !== "/signin" && (
        <Header />
      )}
      <CSKH/>
      <ToastNotification/>
      <main className="container mx-auto font-poppins">
        <Routes>
          <Route path="/" element={<Home />} />
          <Route path="/about" element={<About />} />
          <Route path="/services" element={<Services />} />
          <Route path="/tickets" element={<Tickets />} />
          <Route path="/orders" element={<Orders />} />
          <Route path="/news" element={<News />} />
          <Route path="/contact" element={<Contact />} />
          <Route path="/careers" element={<Careers />} />
          <Route
            path="/news/:postId"
            element={
                <NewsDetail />
            }
          />
          <Route
            path="/search"
            element={
                <SearchResults />
            }
          />
          <Route path="/payment-status" element={<PaymentStatus />} />
          <Route path="/signin" element={<SignIn />} />
          <Route path="/signup" element={<SignUp />} />
          <Route path="/forgot-password" element={<ForgotPassword />} />
          <Route path="/reset-password" element={<ResetPassword />} />
          <Route path="/profile" element={<ProFile/>} />
          <Route path="/booking-history" element={<TicketHistory/>} />
          <Route path="/ticketdetail/:ticket_id" element={<TicketDetail/>}/>
          <Route path="/notifications" element={<NotificationPage/>} />
        </Routes>
      </main>
      {location.pathname !== "/signup" && location.pathname !== "/signin" && (
        <Footer />
      )}
    </>
  );
}

function App() {
  return (
    <Router>
      <ScrollToTop />
      <AuthProvider>
        <NotificationProvider>
          <Layout />
        </NotificationProvider>
      </AuthProvider>
    </Router>
  );
}
export default App;
